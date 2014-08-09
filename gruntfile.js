module.exports = function (grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		clean: {
			assets:  ['www/assets/']
		},

		less: {
			app: {
				options: {
					paths: ['assets/less'],
					compress: true
				},
				files: {
					'tmp/app.css': 'assets/app.less'
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
					'assets/vendor/html.sortable/src/html.sortable.js', // TODO: minify this manually (don't use the prebuilt dist one cause it has its version number in the filename...)
				],
				dest: 'www/assets/js/vendor.backend.js'
			},

			css: {
				options: {
					separator: '\n'
				},
				src: [
					'assets/vendor/bootswatch/yeti/bootstrap.min.css',
					'assets/vendor/pickadate/lib/themes/classic.css',
					'assets/vendor/pickadate/lib/themes/classic.date.css',
					'assets/vendor/pickadate/lib/themes/classic.time.css',
					'tmp/app.css'
				],
				dest: 'www/assets/app.css'
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
				tasks: ['css']
			}
		}
	});

	// load tasks
	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-less');
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-shell');
	grunt.loadNpmTasks('grunt-lineending');

	// register custom tasks
	grunt.registerTask('css',      ['less:app', 'concat:css']);
	grunt.registerTask('js',       ['concat:vendor_backend']);
	grunt.registerTask('assets',   ['clean:assets', 'css', 'js']);
	grunt.registerTask('doctrine', ['shell:schema', 'lineending:schema', 'shell:proxies']);
	grunt.registerTask('default',  ['assets']);
};
