/* configuration ----------------------------------------------------------- */
var browserSyncProxy = 'http://localhost/www/fa/fatmodular';

/* system ------------------------------------------------------------------ */
var gulp = require('gulp');
var browserSync = require('browser-sync').create();
var $ = require('gulp-load-plugins')({
    pattern: ['gulp-*', 'gulp.*', 'main-bower-files', 'stream-series']
});
var assetInjector = function(filepath) {
    if (filepath.slice(-4) === '.css') {
        return '<link rel="stylesheet" href="{{ \''+filepath.substr(1)+'\'|path }}">';
    }
    if (filepath.slice(-3) === '.js') {
        return '<script src="{{ \''+filepath.substr(1)+'\'|path }}"></script>';
    }
    // Use the default transform as fallback:
    return $.inject.transform.apply($.inject.transform, arguments);
}

/* tasks ------------------------------------------------------------------- */

gulp.task('inject-asset', ['copy-fonts'], function() {
    var vendorStyleStream = gulp.src($.mainBowerFiles('**/*.css'))
        .pipe($.concat('vendor.css'))
        .pipe(gulp.dest('asset/css'))
        .pipe(browserSync.stream());
    var vendorScriptStream = gulp.src($.mainBowerFiles('**/*.js'))
        .pipe($.concat('vendor.js'))
        .pipe(gulp.dest('asset/js'))
        .pipe(browserSync.stream());
    var bowerAllStream = gulp.src($.mainBowerFiles('**/*.less'), {read:false})
    var appStream = gulp.src([
        'asset/js/*.js',
        'asset/css/*.css',
        '!asset/**/vendor.{css,js}',
        ], {read: false});

    gulp.src('dev/less/app.less')
        .pipe($.inject($.streamSeries(bowerAllStream), {name: 'bower', addRootSlash:false, transform: assetInjector}))
        .pipe(gulp.dest('dev/less'))
    gulp.src('app/view/layout/*.html')
        .pipe($.inject($.streamSeries(vendorScriptStream, vendorStyleStream), {name:'bower', transform: assetInjector}))
        .pipe($.inject($.streamSeries(appStream), {transform: assetInjector}))
        .pipe(gulp.dest('app/view/layout'));
});

gulp.task('copy-fonts', function() {
    gulp.src('bower_components/**/*.{eot,svg,ttf,woff,woff2}')
        .pipe($.flatten())
        .pipe(gulp.dest('asset/fonts'))
});

gulp.task('compile-style', function() {
    gulp.src('dev/less/app.less')
        .pipe($.less({outputStyle: 'nested'}))
        .pipe(gulp.dest('asset/css'))
        .pipe(browserSync.stream());
});

gulp.task('watch-changes', function() {
    browserSync.init({
        proxy: browserSyncProxy,
        browser: ["chrome"],
        ghostMode: false,
        notify: true,
        online: false
    });
    gulp.watch('dev/less/**/*.less', ['compile-style']);
    gulp.watch('bower.json', ['inject-asset']);
    gulp.watch(['app/**/*','asset/js/**/*']).on('change', browserSync.reload);
});

gulp.task('default', ['compile-style','inject-asset','watch-changes']);
