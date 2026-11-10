/* Loads blogs published from the admin panel into #blogGrid on blog.html.
   If the PHP backend is unavailable (e.g. static hosting) the page simply
   keeps its built-in cards. */
(function () {
    'use strict';

    var grid = document.getElementById('blogGrid');
    if (!grid || !window.fetch) {
        return;
    }

    function esc(value) {
        var div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function card(post) {
        var col = document.createElement('div');
        col.className = 'col-lg-4 col-md-6 col-sm-6';
        col.innerHTML =
            '<article class="blog-card">' +
                '<div class="blog-media">' +
                    '<img src="' + esc(post.image) + '" alt="' + esc(post.title) + '" width="800" height="500" loading="lazy">' +
                    (post.category ? '<span class="blog-tag">' + esc(post.category) + '</span>' : '') +
                '</div>' +
                '<div class="blog-body">' +
                    '<div class="blog-meta">' +
                        '<i class="bi bi-calendar3" aria-hidden="true"></i> ' + esc(post.date) +
                        ' <span aria-hidden="true">&bull;</span> ' + esc(post.minutes) + ' min read' +
                    '</div>' +
                    '<h3><a href="' + esc(post.url) + '">' + esc(post.title) + '</a></h3>' +
                    '<p>' + esc(post.excerpt) + '</p>' +
                    '<a class="link-arrow" href="' + esc(post.url) + '">Read More <i class="bi bi-arrow-right" aria-hidden="true"></i></a>' +
                '</div>' +
            '</article>';
        return col;
    }

    fetch('api/posts.php', { headers: { Accept: 'application/json' } })
        .then(function (res) { return res.ok ? res.json() : null; })
        .then(function (data) {
            if (!data || !data.ok || !data.posts.length) {
                return;
            }
            var fragment = document.createDocumentFragment();
            data.posts.forEach(function (post) { fragment.appendChild(card(post)); });
            grid.insertBefore(fragment, grid.firstChild);
        })
        .catch(function () { /* keep the static cards */ });
})();
