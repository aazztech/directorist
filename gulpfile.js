const gulp = require('gulp');
const sass = require('gulp-sass');
const sourcemap = require('gulp-sourcemaps');
const autoPrefixer = require('gulp-autoprefixer');
const rtlcss = require('gulp-rtlcss');
const rename = require('gulp-rename');
const gulpfilter = require('gulp-filter');
const wpPot = require('gulp-wp-pot');

/* sass compiler */
function compileSass(src, dest) {
        return async function() {
                gulp.src(src)
                        .pipe(sourcemap.init())
                        .pipe(sass({ outputStyle: 'expanded' }).on('error', sass.logError))
                        .pipe(autoPrefixer('last 10 versions'))
                        .pipe(sourcemap.write('map'))
                        .pipe(gulp.dest(dest));
        };
}

// compile bootstrap
gulp.task('bs', compileSass('public/assets/bootstrap/bootstrap.scss', 'public/assets/css/'));
gulp.task('style', compileSass('public/assets/scss/style.scss', 'public/assets/css/'));
gulp.task('admin', compileSass('public/assets/scss/admin/style.scss', 'admin/assets/css/'));

// default gulp task\

gulp.task(
        'default',
        gulp.series('bs', 'style', 'admin', function(done) {
                gulp.watch('public/assets/bootstrap/*.scss', gulp.series('bs'));
                gulp.watch('public/assets/scss/**/*.scss', gulp.series('style'));
                gulp.watch(['public/assets/scss/admin/**/*.scss'], gulp.series('admin'));
                done();
        })
);

// rtl css generator
gulp.task('rtl', function(done) {
        const bootstrap = gulpfilter('**/bootstrap.css', { restore: true });
        const style = gulpfilter('**/style.css', { restore: true });
        const search_style = gulpfilter('**/search-style.css', { restore: true });

        gulp.src([
                'public/assets/css/bootstrap.css',
                'public/assets/css/style.css',
                'public/assets/css/search-style.css',
        ])
                .pipe(
                        rtlcss({
                                stringMap: [
                                        {
                                                name: 'left-right',
                                                priority: 100,
                                                search: ['left', 'Left', 'LEFT'],
                                                replace: ['right', 'Right', 'RIGHT'],
                                                options: {
                                                        scope: '*',
                                                        ignoreCase: false,
                                                },
                                        },
                                        {
                                                name: 'ltr-rtl',
                                                priority: 100,
                                                search: ['ltr', 'Ltr', 'LTR'],
                                                replace: ['rtl', 'Rtl', 'RTL'],
                                                options: {
                                                        scope: '*',
                                                        ignoreCase: false,
                                                },
                                        },
                                ],
                        })
                )
                .pipe(bootstrap)
                .pipe(rename({ suffix: '-rtl', extname: '.css' }))
                .pipe(gulp.dest('public/assets/css/'))
                .pipe(bootstrap.restore)

                .pipe(style)
                .pipe(rename({ suffix: '-rtl', extname: '.css' }))
                .pipe(gulp.dest('public/assets/css/'))
                .pipe(style.restore)

                .pipe(search_style)
                .pipe(rename({ suffix: '-rtl', extname: '.css' }))
                .pipe(gulp.dest('public/assets/css/'))
                .pipe(search_style.restore);

                done();
});

gulp.task("make-pot", function() {
  return gulp
    .src("./**/*.php")
    .pipe(
      wpPot({
        domain: "directorist",
        package: "Directorist - Business Directory Plugin"
      })
    )
    .pipe(gulp.dest("./languages/directorist.pot"));
});
