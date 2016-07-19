var path = require('path');

module.exports = function (grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		clean: {
			tmp:     ['tmp/assets/'],
			assets:  ['www/assets/']
		},

		less: {
			app: {
				options: {
					paths: ['assets/less'],
					compress: true
				},
				files: {
					'tmp/assets/css/app-frontend.css': 'assets/less/frontend.less',
					'tmp/assets/css/app-backend.css':  'assets/less/backend.less'
				}
			}
		},

		concat: {
			vendor_js_backend: {
				options: {
					separator: '\n;\n'
				},
				src: [
					'node_modules/pickadate/lib/compressed/picker.js',
					'node_modules/pickadate/lib/compressed/picker.date.js',
					'node_modules/pickadate/lib/compressed/picker.time.js',
					'node_modules/moment/min/moment.min.js',
					'node_modules/bootstrap-notify/bootstrap-notify.min.js',
					'node_modules/knockout-secure-binding/dist/knockout-secure-binding.min.js',
					'node_modules/nativesortable/nativesortable.js',
					'assets/js/knockout.x-editable.patched.js'
				],
				dest: 'tmp/assets/js/vendor-backend.js'
			},

			vendor_js_frontend: {
				options: {
					separator: '\n;\n'
				},
				src: [
					'node_modules/moment/min/moment.min.js',
				],
				dest: 'tmp/assets/js/vendor-frontend.js'
			},

			i18n_en_us: {
				options: {
					separator: '\n;\n',
					footer: 'var horaroTimeFormat = "H:i a";'
				},
				src: [/* english (US) is built in into all dependencies */],
				dest: 'tmp/assets/js/i18n/en_us.js'
			},

			i18n_de_de: {
				options: {
					separator: '\n;\n',
					footer: 'var horaroTimeFormat = "HH:i !U!h!r";'
				},
				src: [
					'node_modules/pickadate/lib/translations/de_DE.js',
					'node_modules/moment/locale/de.js'
				],
				dest: 'tmp/assets/js/i18n/de_de.js'
			},

			vendor_css_backend: {
				options: {
					separator: '\n'
				},
				src: [
					'node_modules/bootswatch/yeti/bootstrap.min.css',
					'node_modules/pickadate/lib/themes/classic.css',
					'node_modules/pickadate/lib/themes/classic.date.css',
					'node_modules/pickadate/lib/themes/classic.time.css',
				],
				dest: 'tmp/assets/css/vendor-backend.css'
			}
		},

		includereplace: {
			app_backend: {
				options: {
					prefix: '\/\/@@'
				},
				files: {
					'tmp/assets/js/app-backend.js': ['assets/js/backend.js']
				}
			},
			app_frontend: {
				options: {
					prefix: '\/\/@@'
				},
				files: {
					'tmp/assets/js/app-frontend.js': ['assets/js/frontend.js']
				}
			}
		},

		cssmin: {
			assets: {
				options: {
					keepSpecialComments: 0
				},
				files: [{
					expand: true,
					cwd: 'tmp/assets/css/',
					src: ['*.css', '!*.min.css'],
					dest: 'www/assets/css/',
					ext: '.min.css'
				}]
			}
		},

		copy: {
			themes: {
				files: [
					{
						expand: true,
						src: ['node_modules/bootswatch/*/*.min.css'],
						dest: 'www/assets/css',
						rename: function(dest, src) {
							return path.join(dest, 'theme-' + path.basename(path.dirname(src)) + '.min.css');
						}
					}
				]
			},
			images: {
				files: [
					{
						expand: true,
						src: ['assets/images/**/*'],
						dest: 'www'
					}
				]
			}
		},

		uglify: {
			assets: {
				files: [{
					expand: true,
					cwd: 'tmp/assets/js',
					src: '**/*.js',
					dest: 'www/assets/js',
					ext: '.min.js'
				}]
			}
		},

		filerev: {
			options: {
				encoding: 'utf8',
				algorithm: 'md5',
				length: 8
			},

			css: {
				src: 'www/assets/css/*.min.css',
				dest: 'www/assets/css'
			},

			js: {
				src: 'www/assets/js/*.js',
				dest: 'www/assets/js'
			},

			images: {
				src: 'www/assets/images/*.*',
				dest: 'www/assets/images'
			},

			images_themes: {
				src: 'www/assets/images/themes/*.png',
				dest: 'www/assets/images/themes'
			},

			images_favicons: {
				src: 'www/assets/images/favicons/*.*',
				dest: 'www/assets/images/favicons'
			},

			i18n: {
				src: 'www/assets/js/i18n/*.js',
				dest: 'www/assets/js/i18n'
			}
		},

		filerev_assets: {
			ship: {
				options: {
					dest: 'tmp/assets.json',
					cwd: 'www/assets/'
				}
			}
		},

		shell: {
			schema: {
				command: 'php vendor/doctrine/orm/bin/doctrine orm:schema-tool:create --dump-sql > resources/schema.sql'
			},
			proxies: {
				command: 'php vendor/doctrine/orm/bin/doctrine orm:generate-proxies'
			},
			entities: {
				command: 'php vendor/doctrine/orm/bin/doctrine orm:generate:entities tmp'
			}
		},

		lineending: {
			options: {
				eol: 'lf'
			},
			schema: {
				files: {
					'resources/schema.sql': ['resources/schema.sql']
				}
			}
		},

		watch: {
			css: {
				files: ['assets/less/*.less'],
				tasks: ['less:app', 'cssmin']
			},
			app: {
				files: ['assets/js/**/*'],
				tasks: ['includereplace', 'uglify']
			}
		}
	});

	// load tasks
	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-contrib-less');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-filerev');
	grunt.loadNpmTasks('grunt-filerev-assets');
	grunt.loadNpmTasks('grunt-include-replace');
	grunt.loadNpmTasks('grunt-lineending');
	grunt.loadNpmTasks('grunt-shell');

	// register custom tasks
	grunt.registerTask('css',      ['less:app', 'concat:vendor_css_backend', 'copy:themes', 'cssmin']);
	grunt.registerTask('js',       ['concat:vendor_js_backend', 'concat:vendor_js_frontend', 'includereplace', 'i18n', 'uglify']);
	grunt.registerTask('i18n',     ['concat:i18n_en_us', 'concat:i18n_de_de']);
	grunt.registerTask('assets',   ['css', 'js', 'copy:images']);
	grunt.registerTask('doctrine', ['shell:schema', 'lineending:schema', 'shell:proxies']);
	grunt.registerTask('version',  ['filerev', 'filerev_assets']);
	grunt.registerTask('default',  ['clean', 'assets']);
	grunt.registerTask('ship',     ['default', 'i18n', 'version']);

	// do not clean www/assets or else cached pages will link to non-existing assets
	// this will also re-version already versioned items, so make sure to call clean
	// from time to time
	grunt.registerTask('prod',     ['clean:tmp', 'assets', 'i18n', 'version']);
};
