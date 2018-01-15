var gulp = require('gulp'),
    concatCss = require('gulp-concat-css'),
    rename = require('gulp-rename'),
    minifyCSS = require('gulp-minify-css');

gulp.task('style:build', function() {
    return gulp.src(['styles/style.css', 'styles/adaptive.css'])
        .pipe(concatCss('bundle.css'))
        .pipe(minifyCSS())
        .pipe(rename('bundle.min.css'))
        .pipe(gulp.dest('styles/'));
});

gulp.task('default', ['style:build']);

gulp.task('watch', function () {
    gulp.watch(['styles/*.css'],['default']);
});