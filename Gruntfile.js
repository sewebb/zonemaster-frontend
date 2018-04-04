'use strict';
module.exports = function( grunt ) {

	var username = process.env.USER || process.env.USERNAME;

	// save sample-env.js as env.js and change your settings
	var envSettings = require('./env.js');

	grunt.loadNpmTasks('grunt-browserify');


	grunt.initConfig({
		clean: {
			dist: [ 'css/*.css','js/*.min.js', 'js/bundle.js' ]
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
					'package.json', '.DS_Store', 'README.md', 'config.rb', '.jshintrc', 'env.js', 'sample-env.js'],
				args: ["-t", "-O", "-p", "--chmod=Du=rwx,Dg=rwx,Do=rx,Fu=rw,Fg=rw,Fo=r"],
				// Our own settings
				basefolder: envSettings.basefolder,
				themefolder: envSettings.themefolder,
				sitename: {
					stagesite: envSettings.stagesite,
					prodsite: envSettings.prodsite,
				},
				hostservers: {
					stageserver: envSettings.stageserver,
					prodserver: envSettings.prodserver,
				}
			},
			stage: {
				options: {
					dest: '<%= rsync.options.basefolder %><%= rsync.options.sitename.stagesite %><%= rsync.options.themefolder %>',
					host: '<%= rsync.options.hostservers.stageserver %>'
				}
			},
			prod: {
				options: {
					dest: '<%= rsync.options.basefolder %><%= rsync.options.sitename.prodsite %><%= rsync.options.themefolder %>',
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
				tasks: [ 'browserify', 'uglify', 'version' ],
				options: {
					spawn: false
				}
			}
		},
		browserify: {
			pkg: grunt.file.readJSON('package.json'),
			options: {
				transform: [['babelify', {presets: ['babel-preset-es2015']}]]
			},
			main: {
				src: 'js/app.js',
				dest: 'js/bundle.js'
			}
		},
		uglify: {
			dist: {
				files: {
					'js/app.min.js': [
						'js/clipboard.js',
						'js/bundle.js'
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

	grunt.registerTask( 'settings', 'test settings in env.js', function( setting ) {
		grunt.log.writeln( 'basefolder: ' + envSettings.basefolder );
		grunt.log.writeln( 'themefolder: ' + envSettings.themefolder );
		grunt.log.writeln( 'stagesite: ' + envSettings.stagesite );
		grunt.log.writeln( 'prodsite: ' + envSettings.prodsite );
		grunt.log.writeln( 'stageserver: ' + envSettings.stageserver );
		grunt.log.writeln( 'prodserver: ' + envSettings.prodserver );
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
