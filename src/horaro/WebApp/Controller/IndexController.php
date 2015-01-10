<?php
/*
 * Copyright (c) 2014, Sgt. Kabukiman, https://bitbucket.org/sgt-kabukiman/
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Controller;

use horaro\Library\Entity\Event;
use horaro\Library\Entity\User;
use horaro\WebApp\Exception as Ex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends BaseController {
	public function welcomeAction(Request $request) {
		// find upcoming event schedules (blatenly ignoring that the starting times
		// in the database are not in UTC).

		$scheduleRepo = $this->getRepository('Schedule');
		$schedules    = $scheduleRepo->findUpcoming(3132, 10);
		$upcoming     = [];

		// group by event
		foreach ($schedules as $schedule) {
			$event   = $schedule->getEvent();
			$eventID = $event->getID();

			$upcoming[$eventID]['event']       = $event;
			$upcoming[$eventID]['schedules'][] = $schedule;
		}

		// find featured, old events
		$ids       = $this->app['runtime-config']->get('featured_events', []);
		$eventRepo = $this->getRepository('Event');
		$featured  = $eventRepo->findById($ids);

		// remove featured events that are already included in the upcoming list
		foreach ($featured as $idx => $event) {
			$eventID = $event->getID();

			if (isset($upcoming[$eventID]) || !$event->isPublic()) {
				unset($featured[$idx]);
			}
		}

		// if someone is logged in, find their recent activity
		$user   = $this->getCurrentUser();
		$recent = [];

		if ($user) {
			$recent = $scheduleRepo->findRecentlyUpdated($user, 7);
		}

		$html = $this->render('index/welcome.twig', [
			'noRegister' => $this->exceedsMaxUsers(),
			'upcoming'   => array_slice($upcoming, 0, 5),
			'featured'   => array_slice($featured, 0, 5),
			'recent'     => $recent
		]);

		return $this->setCachingHeader(new Response($html), 'homepage');
	}

	public function registerFormAction(Request $request) {
		if ($this->exceedsMaxUsers()) {
			return $this->redirect('/');
		}

		$html = $this->render('index/register.twig', ['result' => null]);

		return $this->setCachingHeader(new Response($html), 'other');
	}

	public function registerAction(Request $request) {
		if ($this->exceedsMaxUsers()) {
			return $this->redirect('/');
		}

		$validator = $this->app['validator.createaccount'];
		$result    = $validator->validate([
			'login'        => $request->request->get('login'),
			'password'     => $request->request->get('password'),
			'password2'    => $request->request->get('password2'),
			'display_name' => $request->request->get('display_name')
		]);

		if ($result['_errors']) {
			return new Response($this->render('index/register.twig', ['result' => $result]), 400);
		}

		// create new user

		$config = $this->app['config'];

		$user = new User();
		$user->setLogin($result['login']['filtered']);
		$user->setPassword($this->app['encoder']->encode($result['password']['filtered']));
		$user->setDisplayName($result['display_name']['filtered']);
		$user->setRole($config['default_role']);
		$user->setMaxEvents($config['max_events']);
		$user->setLanguage('en_us');

		$em = $this->getEntityManager();
		$em->persist($user);
		$em->flush();

		// open session

		$session = $this->app['session'];
		$session->start();
		$session->migrate(); // create new session ID (prevents session fixation)
		$session->set('horaro.user', $user->getId());

		$this->app['csrf']->initSession($session);
		$this->addSuccessMsg('Welcome to Horaro, your account has been successfully created.');

		return $this->redirect('/-/home');
	}

	public function loginFormAction(Request $request) {
		$html = $this->render('index/login.twig', ['result' => null]);

		return $this->setCachingHeader(new Response($html), 'other');
	}

	public function loginAction(Request $request) {
		$validator = $this->app['validator.login'];
		$result    = $validator->validate([
			'login'    => $request->request->get('login'),
			'password' => $request->request->get('password')
		]);

		if ($result['_errors']) {
			return new Response($this->render('index/login.twig', ['result' => $result]), 401);
		}

		// open session

		$user    = $result['_user'];
		$session = $this->app['session'];

		$session->start();
		$session->migrate(); // create new session ID (prevents session fixation)
		$session->set('horaro.user', $user->getId());
		$session->set('horaro.pwdhash', sha1($user->getPassword()));

		$this->app['csrf']->initSession($session);

		return $this->redirect('/');
	}

	public function logoutAction(Request $request) {
		session_destroy();

		$response = $this->redirect('/');
		$response->headers->clearCookie($request->getSession()->getName());

		return $response;
	}

	public function licensesAction(Request $request) {
		$html = $this->render('index/licenses.twig');

		return $this->setCachingHeader(new Response($html), 'other');
	}

	public function calendarAction(Request $request) {
		$year  = (int) $request->attributes->get('year');
		$month = (int) $request->attributes->get('month');

		$minYear = 2000;
		$maxYear = date('Y') + 10;

		if ($year < $minYear || $year > $maxYear) {
			throw new Ex\NotFoundException('Invalid year given.');
		}

		if ($month < 1 || $month > 12) {
			throw new Ex\NotFoundException('Invalid month given.');
		}

		$firstDay = new \DateTime(sprintf('%04d-%02d-01', $year, $month));
		$calendar = new \Solution10\Calendar\Calendar($firstDay);
		$calendar->setResolution(new \Solution10\Calendar\Resolution\MonthResolution());

		$viewData = $calendar->viewData();
		$month    = $viewData['contents'][0];

		$calStart = $firstDay->format('Y-m-d');
		$calEnd   = $month->lastDay()->format('Y-m-d');

		// find date range shown in the actual calendar (which shows overflowing dates)

		$firstWeek = null;
		$lastWeek  = null;

		foreach ($month->weeks() as $week) {
			if ($firstWeek === null) {
				$firstWeek = $week;
			}

			$lastWeek = $week;
		}

		$calViewStart = $firstWeek->weekStart()->format('Y-m-d');
		$calViewEnd   = $lastWeek->weekEnd()->format('Y-m-d');

		// define range for the database query
		// Since the database doesn't know the end, we must manually make sure that schedules that
		// start before $calStart but end after $claStart are included. To do so, we set the search
		// beginning date one month back. Should work well enough.

		$queryBegin = clone $firstDay;
		$queryEnd   = new \DateTime($calEnd.' 23:59:59');

		$queryBegin->modify('-1 month');

		$scheduleRepo = $this->getRepository('Schedule');
		$schedules    = $scheduleRepo->findPublic($queryBegin, $queryEnd);

		// collect schedule ranges, grouped by event
		// collapse schedules of the same event with the same date range

		$ranges = []; // {eventID: {'from-to': <schedule|event>, ...}, eventID: ...}

		foreach ($schedules as $schedule) {
			$event   = $schedule->getEvent();
			$start   = $schedule->getLocalStart()->format('Y-m-d');
			$end     = $schedule->getLocalEnd()->format('Y-m-d');
			$eventID = $event->getID();
			$range   = "$start-$end";

			// now that we know the start and end, we can filter out schedules that are not relevant
			// to this calendar view
			if ($end < $calStart || $start > $calEnd) {
				continue;
			}

			if (!isset($ranges[$eventID][$range])) {
				$ranges[$eventID][$range] = [$schedule, $schedule];
			}
			else {
				$ranges[$eventID][$range] = [$schedule, $event];
			}
		}

		// collect remaining schedules
		// also, count how often we see each event in this calendar view

		$calElements = [];
		$eventCounts = [];

		foreach ($ranges as $eventID => $dateranges) {
			foreach ($dateranges as $x) {
				$calElements[] = $x;
				$eventID       = $x[0]->getEvent()->getID();

				if (!isset($eventCounts[$eventID])) {
					$eventCounts[$eventID] = 0;
				}

				$eventCounts[$eventID]++;
			}
		}

		// collect raw schedule data, grouped by day

		$data       = []; // {YYYY-MM-DD: {scheduleID: info, scheduleID: info, ...}}
		$lengths    = []; // {scheduleID: numofdays, scheduleID: numofdays, ...}
		$calStartTS = strtotime($calViewStart);

		foreach ($calElements as $calElement) {
			list ($schedule, $linkTo) = $calElement;

			$id     = $schedule->getID();
			$event  = $schedule->getEvent();
			$start  = strtotime($schedule->getLocalStart()->format('Y-m-d'));
			$end    = strtotime($schedule->getLocalEnd()->format('Y-m-d'));
			$cursor = $start;
			$len    = 0;

			if ($linkTo instanceof Event) {
				$title = $linkTo->getName();
				$url   = '/'.$linkTo->getSlug();
			}
			elseif ($eventCounts[$event->getID()] < 2) {
				$title = $event->getName();
				$url   = '/'.$event->getSlug().'/'.$schedule->getSlug();
			}
			else {
				$title = $event->getName(). ' ('.$schedule->getName().')';
				$url   = '/'.$event->getSlug().'/'.$schedule->getSlug();
			}

			// walk through the scheduler date range, day by day, and add one element
			// per day to $data

			while ($cursor <= $end) {
				$date  = date('Y-m-d', $cursor);
				$state = 'progress';

				if ($start === $end) {
					$state = 'single';
				}
				elseif ($cursor === $start) {
					$state = 'begin';
				}
				elseif ($cursor === $end) {
					$state = 'end';
				}

				$data[$date][$id] = [
					'id'        => $id,
					'state'     => $state,
					'group'     => $event->getID(),
					'title'     => $title,
					'url'       => $url,
					'continued' => in_array($state, ['progress', 'end']) && $cursor === $calStartTS
				];

				$cursor = strtotime('+1 day', $cursor);
				$len   += 1;
			}

			$lengths[$id] = $len;
		}

		// sort by date
		ksort($data);

		// remove dates prior/after the selected month

		foreach (array_keys($data) as $day) {
			if ($day < $calViewStart || $day > $calViewEnd) {
				unset($data[$day]);
			}
		}

		// build up the stacks. in the calendar, each day has a stack of rows, up to $height many.

		$stacks    = [];
		$height    = 5;
		$yesterday = [];

		foreach ($data as $day => $things) {
			$stack = [];

			// sort things of this day descending by their length
			uasort($things, function($a, $b) use ($lengths) {
				return $lengths[$b['id']] - $lengths[$a['id']];
			});

			// continue things from yesterday
			foreach ($yesterday as $pos => $value) {
				$thingID = $value['id'];

				if (isset($things[$thingID])) {
					$stack[$pos] = $things[$thingID];
					unset($things[$thingID]);
				}
			}

			// add remaining things to the free slots
			for ($i = 0; $i < $height && count($things) > 0; ++$i) {
				if (!isset($stack[$i])) {
					reset($things);

					$thingID   = key($things);
					$stack[$i] = $things[$thingID];

					unset($things[$thingID]);
				}
			}

			// we will make sure that $stack is properly sorted later when we insert the filler elements

			$stacks[$day] = $stack;
			$yesterday    = $stack;
		}

		// Now we can assign colors to each bar in the calendar. For this we choose of a predefined
		// list of colors. There are two priorities: If two things that belong together appear in the
		// calendar (think to schedules of the same event), they should share the same color. Also,
		// each calendar day should only unique colors. The first requirement has priority.

		$colors          = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
		$colorsNeverUsed = $colors; // never used on this page yet
		$colorsPerGroup  = [];
		$yesterday       = [];

		shuffle($colorsNeverUsed);

		foreach ($stacks as $day => &$stack) {
			$colorsAvailable = $colors;

			shuffle($colorsAvailable);

			foreach ($stack as $i => $element) {
				$previous = isset($yesterday[$i]) ? $yesterday[$i] : null;
				$group    = $element['group'];

				// if we continue a line from the previous day, we MUST use the same color
				if ($previous['id'] === $element['id']) {
					$color = $previous['color'];
				}

				// otherwise, see if we already have a color for this group
				elseif (isset($colorsPerGroup[$group])) {
					$color = $colorsPerGroup[$group];
				}

				// choose a new color
				else {
					if (!empty($colorsNeverUsed)) {
						$color = array_pop($colorsNeverUsed);
					}
					else {
						$color = array_pop($colorsAvailable);
					}
				}

				// remove this color from the list of colors available for today
				$idx = array_search($color, $colorsAvailable);
				if ($idx !== false) unset($colorsAvailable[$idx]);

				$idx = array_search($color, $colorsNeverUsed);
				if ($idx !== false) unset($colorsNeverUsed[$idx]);

				$stack[$i]['color'] = $color;
			}

			$yesterday = $stack;
		}

		// fill in gaps with filler elements

		foreach ($stacks as $day => &$stack) {
			$fill = false;

			for ($i = $height - 1; $i >= 0; --$i) {
				if (isset($stack[$i])) {
					$fill = true;
				}
				elseif ($fill) {
					$stack[$i] = 'fill';
				}
			}

			ksort($stack);
		}

		$prevMonth = clone $month->firstDay();
		$nextMonth = clone $month->firstDay();
		$prevYear  = clone $month->firstDay();
		$nextYear  = clone $month->firstDay();

		$prevMonth->modify('-1 month');
		$nextMonth->modify('+1 month');
		$prevYear->modify('-1 year');
		$nextYear->modify('+1 year');

		$html = $this->render('index/calendar.twig', [
			'stacks'    => $stacks,
			'month'     => $month,
			'prevMonth' => $prevMonth,
			'nextMonth' => $nextMonth,
			'prevYear'  => $prevYear,
			'nextYear'  => $nextYear,
			'minYear'   => $minYear,
			'maxYear'   => $maxYear
		]);

		return $this->setCachingHeader(new Response($html), 'calendar');
	}
}
