module.exports = function (grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		clean: {
			assets:  ['www/assets/', 'tmp/assets/']
		},

		less: {
			app: {
				options: {
					paths: ['assets/less'],
					compress: true
				},
				files: {
					'tmp/assets/css/app.css': 'assets/app.less'
				}
			}
		},

		concat: {
			vendor_backend: {
				options: {
					separator: '\n;\n'
				},
				src: [
					'assets/vendor/pickadate/lib/compressed/picker.js',
					'assets/vendor/pickadate/lib/compressed/picker.date.js',
					'assets/vendor/pickadate/lib/compressed/picker.time.js',
					'assets/vendor/moment/min/moment.min.js',
					'assets/vendor/bootstrap-growl/jquery.bootstrap-growl.min.js',
					'assets/js/html.sortable.patched.js',      // TODO: minify this manually
					'assets/js/knockout.x-editable.patched.js' // TODO: minify this manually
				],
				dest: 'tmp/assets/js/vendor-backend.js'
			},

			vendor_frontend: {
				options: {
					separator: '\n;\n'
				},
				src: [
					'assets/vendor/moment/min/moment.min.js',
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
					'assets/vendor/pickadate/lib/translations/de_DE.js',
					'assets/vendor/moment/locale/de.js'
				],
				dest: 'tmp/assets/js/i18n/de_de.js'
			},

			vendor_css: {
				options: {
					separator: '\n'
				},
				src: [
					'assets/vendor/bootswatch/yeti/bootstrap.min.css',
					'assets/vendor/pickadate/lib/themes/classic.css',
					'assets/vendor/pickadate/lib/themes/classic.date.css',
					'assets/vendor/pickadate/lib/themes/classic.time.css',
				],
				dest: 'tmp/assets/css/vendor.css'
			}
		},

		rig: {
			app_backend: {
				files: {
					'tmp/assets/js/app-backend.js': ['assets/js/backend.js']
				}
			},
			app_frontend: {
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
			schema: {
				files: {
					'resources/schema.sql': ['resources/schema.sql']
				}
			}
		},

		watch: {
			css: {
				files: ['assets/app.less'],
				tasks: ['less:app', 'cssmin']
			},
			app: {
				files: ['assets/js/**/*'],
				tasks: ['rig', 'uglify']
			}
		}
	});

	// load tasks
	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-contrib-less');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-filerev');
	grunt.loadNpmTasks('grunt-filerev-assets');
	grunt.loadNpmTasks('grunt-lineending');
	grunt.loadNpmTasks('grunt-rigger');
	grunt.loadNpmTasks('grunt-shell');

	// register custom tasks
	grunt.registerTask('css',      ['less:app', 'concat:vendor_css', 'cssmin']);
	grunt.registerTask('js',       ['concat:vendor_backend', 'concat:vendor_frontend', 'rig', 'i18n', 'uglify']);
	grunt.registerTask('i18n',     ['concat:i18n_en_us', 'concat:i18n_de_de']);
	grunt.registerTask('assets',   ['clean:assets', 'css', 'js']);
	grunt.registerTask('doctrine', ['shell:schema', 'lineending:schema', 'shell:proxies']);
	grunt.registerTask('ship',     ['filerev', 'filerev_assets']);
	grunt.registerTask('default',  ['assets']);
};
