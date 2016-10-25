'use strict';
module.exports = function( grunt ) {

	var username = process.env.USER || process.env.USERNAME;

	grunt.initConfig({
		clean: {
			dist: [ 'css/*.css','js/*.min.js' ]
		},
		cssmin: {
			target: {
				files: {
					'css/app.min.css': 'css/app.css'
				}
			}
		},
		gitinfo: {
			options: {
				cwd: '.'
			}
		},
		htmlhint_inline: {
			options: {
				htmlhintrc: 'test/.htmlhintrc',
				ignore: {
					'<?php': '?>'
				}
			},
			dest: {
				src: [ '*.html', '*.php' ]
			}
		},
		jscs: {
			src: [ 'Gruntfile.js', 'js/app.js' ],
			options: {
				config: 'test/.jscsrc',
				esnext: true, // If you use ES6 http://jscs.info/overview.html#esnext
				verbose: true, // If you need output with rule names http://jscs.info/overview.html#verbose
				fix: true, // Autofix code style violations when possible.
				requireCurlyBraces: [ 'if' ]
			}
		},
		jshint: {
			options: {
				jshintrc: '.jshintrc'
			},
			all: [
				'<%= jscs.src %>'
			]
		},
		jsvalidate: {
			files: [
				'<%= jscs.src %>'
			]
		},
		phpcs: {
			application: {
				src: ['./*.php']
			},
			options: {
				bin: 'phpcs -p --standard=test/wordpress-rules.xml'
			}
		},
		postcss: {
			options: {
				map: false, // inline sourcemaps
				processors: [
					require('autoprefixer')( { browsers: 'last 2 versions, ie 8-11' } ), // add vendor prefixes
					require('pixrem')(), // add fallbacks for rem units
				]
			},
			dist: {
				src: 'css/*.css'
			}
		},
		// deploy via rsync
		// needs grunt-rsync ~0.6.2 (package.json)
		rsync: {
			// your settings for deploy
			options: {
				src: './',
				recursive: true,
				deleteAll: false,
				exclude: ['.git*', 'node_modules', 'wordpress-rules.xml', 'Gruntfile.js',
					'package.json', '.DS_Store', 'README.md', 'config.rb', '.jshintrc'],
				args: ["-t", "-O", "-p", "--chmod=Du=rwx,Dg=rwx,Do=rx,Fu=rw,Fg=rw,Fo=r"],
				// Our own settings
				basefolder: '/var/www/sites/',
				themefolder: '/wp-content/themes/zonemaster-frontend',
				hostservers: {
					stageserver: 'externalweb.stage.example.com',
					prodserver: 'externalweb.common.example.com'
				}
			},
			stage: {
				options: {
					dest: '<%= rsync.options.basefolder %>stage.zonemaster.example.com<%= rsync.options.themefolder %>',
					host: '<%= rsync.options.hostservers.stageserver %>'
				}
			},
			prod: {
				options: {
					dest: '<%= rsync.options.basefolder %>zonemaster.example.com<%= rsync.options.themefolder %>',
					host: '<%= rsync.options.hostservers.prodserver %>'
				}
			},
		},
		sass: {
			options: {
				includePaths: ['node_modules/foundation-sites/scss/']
			},
			dist: {
				files: {
					'css/app.css': ['scss/app.scss']
				}
			}
		},
		scsslint: {
			allFiles: [ 'scss/**/*.scss'  ],
			options: {
				config: 'test/.scss-lint.yml',
				colorizeOutput: true,
				force: true
			}
		},
		version: {
			assets: {
				src: ['css/app.min.css',
					'js/app.min.js'],
				dest: 'inc/scripts.php'
			}
		},
		watch: {
			css: {
				files: [ 'scss/*/*.scss', 'scss/*.scss' ],
				tasks: [ 'sass', 'cssmin', 'version' ],
				options: {
					spawn: false
				}
			},
			js: {
				files: [ 'js/app.js', 'js/api.js', 'Gruntfile.js' ],
				tasks: [ 'uglify', 'version' ],
				options: {
					spawn: false
				}
			}
		},
		uglify: {
			dist: {
				files: {
					'js/app.min.js': [
						'node_modules/foundation-sites/js/foundation.core.js', // MUST USE
						'node_modules/foundation-sites/js/foundation.util.mediaQuery.js', // MUST USE
						// 'node_modules/foundation-sites/js/foundation.abide.js',
						// 'node_modules/foundation-sites/js/foundation.accordion.js',
						'node_modules/foundation-sites/js/foundation.accordionMenu.js',
						'node_modules/foundation-sites/js/foundation.drilldown.js',
						'node_modules/foundation-sites/js/foundation.dropdown.js',
						'node_modules/foundation-sites/js/foundation.dropdownMenu.js',
						// 'node_modules/foundation-sites/js/foundation.equalizer.js',
						// 'node_modules/foundation-sites/js/foundation.interchange.js',
						// 'node_modules/foundation-sites/js/foundation.magellan.js',
						// 'node_modules/foundation-sites/js/foundation.offcanvas.js',
						// 'node_modules/foundation-sites/js/foundation.orbit.js',
						'node_modules/foundation-sites/js/foundation.responsiveMenu.js', //
						'node_modules/foundation-sites/js/foundation.responsiveToggle.js', //
						'node_modules/foundation-sites/js/foundation.reveal.js',
						// 'node_modules/foundation-sites/js/foundation.slider.js',
						'node_modules/foundation-sites/js/foundation.sticky.js',
						'node_modules/foundation-sites/js/foundation.tabs.js',
						'node_modules/foundation-sites/js/foundation.toggler.js',
						'node_modules/foundation-sites/js/foundation.util.box.js',
						'node_modules/foundation-sites/js/foundation.util.keyboard.js',
						'node_modules/foundation-sites/js/foundation.util.motion.js',
						'node_modules/foundation-sites/js/foundation.util.nest.js',
						'node_modules/foundation-sites/js/foundation.util.timerAndImageLoader.js',
						'node_modules/foundation-sites/js/foundation.util.touch.js',
						'node_modules/foundation-sites/js/foundation.util.triggers.js',

						'js/clipboard.js',

						'js/api.js',
						'js/app.js'
					]
				}
			}
		},
	});

	// Load tasks
	require( 'matchdep' ).filterDev( 'grunt-*' ).forEach( grunt.loadNpmTasks );

	grunt.registerTask( 'default', [
		'clean',
		'sass',
		'postcss',
		'cssmin',
		'uglify',
		'version'
	]);

	grunt.registerTask('multiversion', 'more than one file to wp-version', function(mode) {
		var config = {
			assets: {
				src: ['js/app.min.js','css/app.min.css'],
				dest: 'inc/scripts.php'
			}
		};
		grunt.config.set('version', config);
		grunt.task.run('version');
	});

	grunt.registerTask( 'deploy', 'deploy code to stage or prod', function( target ) {
		if ( null === target ) {
			return grunt.warn( 'Build target must be specified, like deploy:prod.' );
		}
		grunt.task.run( 'gitinfo' );
		grunt.task.run( 'default' );
		grunt.task.run( 'rsync:' + target );
	});

	grunt.registerTask('dev', [
		'watch',
	]);

	grunt.registerTask( 'test', [
		'scsslint',
		'jshint',
		'jsvalidate',
		'jscs',
		'phpcs',
		'htmlhint_inline'
	]);
};
