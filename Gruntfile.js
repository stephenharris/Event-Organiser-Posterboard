module.exports = function(grunt) {

   require('load-grunt-tasks')(grunt);

  // Project configuration.
  grunt.initConfig({
	pkg: grunt.file.readJSON('package.json'),
	uglify: {
		options: {
			compress: {
				global_defs: {
					"EO_SCRIPT_DEBUG": false
				},
				dead_code: true
      			},
			banner: '/*! <%= pkg.name %> <%= pkg.version %> */\n'
		},
		build: {
      			files: [
			        {
			          expand: true,     // Enable dynamic expansion.
			          src: ['js/*.js', '!js/*.min.js' ], // Actual pattern(s) to match.
			          ext: '.min.js',   // Dest filepaths will have this extension.
			        },
			]
		}
	},
	jshint: {
		options: {
			reporter: require('jshint-stylish'),
			globals: {
				"EO_SCRIPT_DEBUG": false,
			},
			'-W020': true, //Read only - error when assigning EO_SCRIPT_DEBUG a value.
		},
		all: [ 'js/*.js', '!js/*.min.js' ]
  	},

	//Compress build directory into <name>.zip and <name>-<version>.zip
	compress: {
		main: {
			options: {
				mode: 'zip',
				archive: './build/<%= pkg.name %>.zip'
			},
			expand: true,
			cwd: 'build/<%= pkg.name %>/',
			src: ['**/*'],
			dest: '<%= pkg.name %>/'
		},
		version: {
			options: {
				mode: 'zip',
				archive: './build/<%= pkg.name %>-<%= pkg.version %>.zip'
			},
			expand: true,
			cwd: 'build/<%= pkg.name %>/',
			src: ['**/*'],
			dest: '<%= pkg.name %>/'
		}	
	},

	//Clean up build directory
	clean: {

		main: ['build/<%= pkg.name %>']
	},

	// Copy the plugin into the build directory
	copy: {
		main: {
			src:  [
				'**',
				'!node_modules/**',
				'!build/**',
				'!.git/**',
				'!Gruntfile.js',
				'!package.json',
				'!.gitignore',
				'!.gitmodules',
				'!**/*~'
			],
			dest: 'build/<%= pkg.name %>/'
		}		
	},

	wp_readme_to_markdown: {
		convert:{
			files: {
				'readme.md': 'readme.txt'
			},
		},
	},
	
	po2mo: {
		files: {
    			src: 'languages/*.po',
			expand: true,
		},
	},

	pot: {
		options:{
        	text_domain: 'event-organiser-posterboard',
	        dest: 'languages/',
			keywords: [
				'__:1',
				'_e:1',
				'_x:1,2c',
				'esc_html__:1',
				'esc_html_e:1',
				'esc_html_x:1,2c',
				'esc_attr__:1', 
				'esc_attr_e:1', 
				'esc_attr_x:1,2c', 
				'_ex:1,2c',
				'_n:1,2', 
				'_nx:1,2,4c',
				'_n_noop:1,2',
				'_nx_noop:1,2,3c'
			],
			},
    	files:{
		src:  [
			'**/*.php',
			'!node_modules/**',
			'!build/**',
			'!**/*~',
		],
	expand: true,
		}
	},

	checktextdomain: {
		options:{
			text_domain: 'event-organiser-posterboard',
			correct_domain: true,
			keywords: [
			'__:1,2d',
			'_e:1,2d',
			'_x:1,2c,3d',
			'esc_html__:1,2d',
			'esc_html_e:1,2d',
			'esc_html_x:1,2c,3d',
			'esc_attr__:1,2d', 
			'esc_attr_e:1,2d', 
			'esc_attr_x:1,2c,3d', 
			'_ex:1,2c,3d',
			'_n:1,2,4d', 
			'_nx:1,2,4c,5d',
			'_n_noop:1,2,3d',
			'_nx_noop:1,2,3c,4d'
			],
		},
		files: {
			src:  [
			'**/*.php',
			'!node_modules/**',
			'!build/**',
			'!**/*~',
			],
			expand: true,
		},
	},

	checkrepo: {
		deploy: {
			tag: {
				eq: '<%= pkg.version %>',    // Check if highest repo tag is equal to pkg.version
			},
			tagged: true, // Check if last repo commit (HEAD) is not tagged
			clean: true,   // Check if the repo working directory is clean
	    	}
	},

	checkwpversion: {
		plugin_equals_stable: {
			version1: 'plugin',
	    		version2: 'readme',
			compare: '!=',
		},
		plugin_equals_package: {
	    		version1: 'plugin',
			version2: '<%= pkg.version %>',
			compare: '==',
		},
	},

    
	wp_deploy: {
	    	deploy:{
			options: {
				svn_user: 'stephenharris',
				plugin_slug: 'event-organiser-posterboard',
				build_dir: 'build/event-organiser-posterboard/'
			},	
		}
	},
    
});


// Default task(s).
grunt.registerTask( 'default', ['jshint', 'uglify'] );
	
grunt.registerTask( 'test', [ 'jshint', 'checktextdomain' ] );

grunt.registerTask( 'build', [ 'test', 'newer:uglify', 'pot', 'newer:po2mo', 'wp_readme_to_markdown', 'clean', 'copy' ] );

grunt.registerTask( 'deploy', [ 'checkwpversion', 'checkbranch:master', 'checkrepo:deploy', 'build', 'wp_deploy', 'compress' ] );
	
};