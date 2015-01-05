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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SitemapController extends BaseController {
	public function generateAction(Request $request) {
		$xml = new \XMLWriter();
		$xml->openMemory();
		$xml->startDocument('1.0', 'UTF-8');

		$xml->startElement('urlset');
			$xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

			$this->addUrl($xml, '/',           null, 'hourly', 1);
			$this->addUrl($xml, '/-/licenses', null, 'weekly', 0.1);
			$this->addUrl($xml, '/-/calendar', null, 'daily',  0.3);
			$this->addEvents($xml);
		$xml->endElement();

		$response = new Response($xml->flush(), 200, ['content-type' => 'text/xml; charset=utf-8']);
		$response->setExpires(new \DateTime('now +1 hour'));

		return $response;
	}

	protected function addEvents(\XMLWriter $xml) {
		$repo   = $this->getRepository('Event');
		$events = $repo->findPublic();

		foreach ($events as $event) {
			$this->addUrl($xml, '/'.$event->getSlug(), null, 'weekly', 0.6);

			foreach ($event->getSchedules() as $schedule) {
				if ($schedule->isPublic()) {
					$this->addUrl($xml, '/'.$event->getSlug().'/'.$schedule->getSlug(), $schedule->getUpdatedAt(), 'hourly', 1);
				}
			}
		}
	}

	protected function addUrl(\XMLWriter $xml, $url, \DateTime $lastmod = null, $changefreq = null, $priority = null) {
		static $root = null;

		if ($root === null) {
			$request = $this->app['request'];
			$root    = $request->getSchemeAndHttpHost();
		}

		$xml->startElement('url');
			$xml->writeElement('loc', $root.$url);

			if ($lastmod) {
				$xml->writeElement('lastmod', $lastmod->format('Y-m-d'));
			}

			if ($changefreq) {
				$xml->writeElement('changefreq', $changefreq);
			}

			if ($priority !== null) {
				$xml->writeElement('priority', sprintf('%.1F', $priority));
			}
		$xml->endElement();
	}
}
