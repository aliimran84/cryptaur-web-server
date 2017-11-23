var gulp = require('gulp'),
    concatCss = require('gulp-concat-css'),
    rename = require('gulp-rename'),
    rev_append = require('gulp-rev-append'),
    minifyCSS = require('gulp-minify-css');

gulp.task('style:build', function() {
    return gulp.src(['styles/style.css', 'styles/adaptive.css'])
        .pipe(concatCss('bundle.css'))
        .pipe(minifyCSS())
        .pipe(rename('bundle.min.css'))
        .pipe(gulp.dest('styles/'));
});

gulp.task('default', ['style:build'], function() {
    gulp.src(['./reg.html', './login.html', './settings.html', './transactions.html', './dashboard.html', './wallet.html'])
        .pipe(rev_append())
        .pipe(gulp.dest('.'));
});

gulp.task('watch', function () {
    gulp.watch(['styles/*.css'],['default']);
});