/* global require */
var distDir = './dist/';

var styleSRC = './assets/scss/*.scss'; // Path to main .scss file
var styleDestination = distDir + 'css/'; // Path to place the compiled CSS file
// Default set to root folder

var jsCustomSRC = './assets/js/*.js'; // Path to JS custom scripts folder
var jsCustomDestination = distDir + 'js/'; // Path to place the compiled JS custom scripts file
var jsCustomFile = 'custom'; // Compiled JS custom file name
// Default set to custom i.e. custom.js

var imageSRC = './assets/img/*'; // Path to images folder
var imageDestination = distDir + 'img/'; // Path to place the compiled JS custom scripts file

var styleWatchFiles = './assets/scss/**/*.scss'; // Path to all *.scss files inside css folder and inside them
var customJSWatchFiles = './assets/js/*.js'; // Path to all custom JS files

/**
 * Load Plugins.
 *
 * Load gulp plugins and assign them semantic names.
 */
var gulp = require('gulp'); // Gulp of-course

// CSS related plugins.
var sass = require('gulp-sass');
var autoprefixer = require('gulp-autoprefixer');
var minifycss = require('gulp-uglifycss');

// JS related plugins.
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');

// Img related plugins.
var imagemin = require('gulp-imagemin');
var cache = require('gulp-cache');

// Utility related plugins.
var rename = require('gulp-rename'); // Renames files E.g. style.css -> style.min.css
var sourcemaps = require('gulp-sourcemaps'); // Maps code in a compressed file (E.g. style.css) back to it’s original position in a source file (E.g. structure.scss, which was later combined with other css files to generate style.css)
var notify = require('gulp-notify'); // Sends message notification to you


/**
 * Task: styles
 *
 * Compiles Sass, Autoprefixes it and Minifies CSS.
 *
 * This task does the following:
 *    1. Gets the source scss file
 *    2. Compiles Sass to CSS
 *    3. Writes Sourcemaps for it
 *    4. Autoprefixes it and generates style.css
 *    5. Renames the CSS file with suffix .min.css
 *    6. Minifies the CSS file and generates style.min.css
 */
gulp.task('styles', function () {
    gulp.src(styleSRC)
        .pipe(sourcemaps.init())
        .pipe(sass({
            errLogToConsole: true,
            outputStyle: 'compact', // 'compressed', 'nested', 'expanded'
            precision: 10
        }))
        .pipe(sourcemaps.write({includeContent: false}))
        .pipe(sourcemaps.init({loadMaps: true}))
        .pipe(autoprefixer(
            'last 2 version',
            '> 1%',
            'safari 5',
            'opera 12.1',
            'ios 10',
            'android 5'))
        .pipe(sourcemaps.write(styleDestination))
        .pipe(gulp.dest(styleDestination))
        .pipe(rename({suffix: '.min'}))
        .pipe(minifycss({
            maxLineLen: 10
        }))
        .pipe(gulp.dest(styleDestination))
        .pipe(notify({message: 'TASK: "styles" Completed!', onLast: true}))
});

/**
 * Task: customJS
 *
 * Concatenate and uglify custom JS scripts.
 *
 * This task does the following:
 *    1. Gets the source folder for JS custom files
 *    2. Concatenates all the files and generates custom.js
 *    3. Renames the JS file with suffix .min.js
 *    4. Uglifes/Minifies the JS file and generates custom.min.js
 */
gulp.task('customJS', function () {
    gulp.src(jsCustomSRC)
        .pipe(concat(jsCustomFile + '.js'))
        .pipe(gulp.dest(jsCustomDestination))
        .pipe(rename({
            basename: jsCustomFile,
            suffix: '.min'
        }))
        .pipe(uglify())
        .pipe(gulp.dest(jsCustomDestination))
        .pipe(notify({message: 'TASK: "customJs" Completed!', onLast: true}));
});

/**
 * Task: images
 *
 * Optimize images.
 */
gulp.task('images', function () {
    return gulp.src(imageSRC)
        .pipe(cache(imagemin({optimizationLevel: 3, progressive: true, interlaced: true})))
        .pipe(gulp.dest(imageDestination))
        .pipe(notify({message: 'TASK: "images" Completed!'}));
});

/**
 * Watch Tasks.
 *
 * Watches for file changes and runs specific tasks.
 */
gulp.task('watch', function () {
    gulp.watch(styleWatchFiles, ['styles']);
    gulp.watch(customJSWatchFiles, ['customJS']);
    gulp.watch(imageSRC, ['images']);

});

gulp.task('default', ['styles', 'customJS', 'images']);