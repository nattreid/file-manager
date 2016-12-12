var gulp = require('gulp'),
    less = require('gulp-less'),
    minify = require('gulp-clean-css'),
    uglify = require('gulp-uglify'),
    concat = require('gulp-concat');

var paths = {
    'dev': {
        'less': './resources/assets/less/',
        'js': './resources/assets/js/',
        'vendor': './resources/assets/vendor/'
    },
    'production': './assets/'
};

gulp.task('js', function () {
    return gulp.src(paths.dev.js + '*.js')
        .pipe(concat('fileManager.js'))
        .pipe(gulp.dest(paths.production));
});

gulp.task('jsMin', function () {
    return gulp.src(paths.dev.js + '*.js')
        .pipe(concat('fileManager.min.js'))
        .pipe(uglify())
        .pipe(gulp.dest(paths.production));
});

gulp.task('css', function () {
    return gulp.src(paths.dev.less + '*.less')
        .pipe(concat('fileManager.css'))
        .pipe(less())
        .pipe(gulp.dest(paths.production));
});

gulp.task('cssMin', function () {
    return gulp.src(paths.dev.less + '*.less')
        .pipe(concat('fileManager.min.css'))
        .pipe(less())
        .pipe(minify({keepSpecialComments: 0}))
        .pipe(gulp.dest(paths.production));
});

gulp.task('watch', function () {
    gulp.watch(paths.dev.js + '*.js', ['js']);
    gulp.watch(paths.dev.less + '*.less', ['less']);
});

gulp.task('default', ['js', 'jsMin', 'css', 'cssMin', 'watch']); 