//IMathAS (c) forums thread-list order cache
//Caches, in localStorage, the ordered list of threads shown by
//thread.php/newthreads.php/flaggedthreads.php/forums.php/index.php for a
//given context, so posts.php can populate its Prev/Next links without a
//DB query. Each cached page is an array of [threadid, forumid] pairs, or
//[threadid, forumid, cid] triples when the entry's course isn't the same
//as the current context's (only index.php's cross-course "allnew" widget
//needs this - every other list lives in a single course, so entries can
//just omit the 3rd element and fall back to ctx.cid).
//The listing pages seed this by scraping their own rendered
//"a.threadlink" links (see seedFromPage) rather than the server sending
//a redundant copy of the same ids as JSON.
var ForumThreadCache = (function() {
	var KEY = 'imasForumThreadList';
	//default/new/flagged are forum-scoped (thread.php); coursenew/
	//courseflagged/threadsearch/postsearch are course-scoped (newthreads.php/
	//flaggedthreads.php/forums.php, which span forums). threadsearch and
	//postsearch are never paginated (always numpages:1) and postsearch is
	//never seeded at all (no stable thread order to cache).
	var FORUM_TYPES = ['default', 'new', 'flagged'];

	function isForumType(type) {
		return FORUM_TYPES.indexOf(type) !== -1;
	}

	function load() {
		try {
			var raw = localStorage.getItem(KEY);
			return raw ? JSON.parse(raw) : null;
		} catch (e) {
			return null;
		}
	}
	function save(data) {
		try {
			localStorage.setItem(KEY, JSON.stringify(data));
		} catch (e) {}
	}

	//scopeid is the forumid for forum-scoped types, or cid for
	//course-scoped types. type is part of the bucket key (not just
	//scopeid) so switching between e.g. a forum's default/new/flagged
	//views doesn't collide with or overwrite each other's cached pages.
	function sameContext(data, ctx) {
		return !!data && data.type === ctx.type && data.scopeid === ctx.scopeid &&
			data.grp === ctx.grp && data.tagfilter === ctx.tagfilter;
	}

	//Called by posts.php, when it resolves a thread by expanding past a
	//cached page boundary, to store that one page's [threadid, forumid]
	//pairs (explicitly - there's no rendered list to scrape there).
	function seed(ctx) {
		var data = load();
		// clear outdated records
		if (!data) {
			data = [];
		}

		let found = false;
		const now = Date.now();
     	const maxAge = 24 * 60 * 60 * 1000;
		for (let i=data.length - 1; i >= 0; i--) {
			if (data[i].timestamp && now - data[i].timestamp > maxAge) {
				data.splice(i,1);
				continue;
			}
			if (sameContext(data[i], ctx)) {
				found = true;
				data[i].pages[ctx.page] = ctx.ids;
				data[i].numpages = ctx.numpages;
				data[i].timestamp = now;
			}
		}
		if (!found) {
			data.push({type: ctx.type, scopeid: ctx.scopeid, grp: ctx.grp, tagfilter: ctx.tagfilter,
				threadsperpage: ctx.threadsperpage || null, numpages: ctx.numpages || null, pages: {},
				timestamp: now});
			data[data.length-1].pages[ctx.page] = ctx.ids;
		}
		/*
		if (!sameContext(data, ctx)) {
			data = {type: ctx.type, scopeid: ctx.scopeid, grp: ctx.grp, tagfilter: ctx.tagfilter,
				threadsperpage: ctx.threadsperpage || null, numpages: ctx.numpages || null, pages: {}};
		} else if (ctx.numpages) {
			data.numpages = ctx.numpages;
		}
		data.pages[ctx.page] = ctx.ids;
		*/
		save(data);
	}

	function parseQueryString(qs) {
		var params = {};
		var pairs = qs.split('&');
		for (var i = 0; i < pairs.length; i++) {
			var kv = pairs[i].split('=');
			if (kv[0]) { params[decodeURIComponent(kv[0])] = decodeURIComponent(kv[1] || ''); }
		}
		return params;
	}

	//Called by thread.php/newthreads.php/flaggedthreads.php/forums.php
	//after rendering their thread list. Rather than the server repeating
	//the list's thread/forum ids as a JSON payload, this scrapes the
	//"a.threadlink" links already on the page (each posts.php?...
	//&forum=X&thread=Y&page=Z[&grp=W]) to rebuild the same [threadid,
	//forumid] pairs, page number, and grp filter - all of which the page
	//already had to send once to render the links themselves.
	//ctx only needs the parts that aren't recoverable from the links:
	//type, tagfilter, and (for paginated listings) numpages/threadsperpage.
	function seedFromPage(ctx) {
		var links = document.querySelectorAll('a.threadlink');
		if (!links.length) { return; }
		var ids = [];
		var page = null, grp = null;
		for (var i = 0; i < links.length; i++) {
			var href = links[i].getAttribute('href') || '';
			var params = parseQueryString(href.split('?')[1] || '');
			var tid = parseInt(params.thread, 10);
			var fid = parseInt(params.forum, 10);
			if (isNaN(tid) || isNaN(fid)) { continue; }
			var linkCid = parseInt(params.cid, 10);
			ids.push(isNaN(linkCid) ? [tid, fid] : [tid, fid, linkCid]);
			if (page === null && params.page !== undefined) { page = parseInt(params.page, 10); }
			if (grp === null && params.grp !== undefined) { grp = parseInt(params.grp, 10); }
		}
		if (!ids.length || page === null || isNaN(page)) { return; }
		seed({
			type: ctx.type,
			scopeid: isForumType(ctx.type) ? ids[0][1] : cid,
			page: page,
			grp: grp,
			tagfilter: ctx.tagfilter || '',
			threadsperpage: ctx.threadsperpage || null,
			numpages: ctx.numpages || null,
			ids: ids
		});
	}

	//Looks for the neighboring thread in direction dir (-1 prev, +1 next)
	//starting from ids[idx] on `page`. Checks the current page first, then
	//falls back to an already-cached adjacent page. Applies uniformly to
	//every paginated type; threadsearch/postsearch always report
	//numpages:1, so page/numpages bounds alone correctly block expansion
	//for those single-page lists without needing a type check here.
	function findNeighbor(data, ctx, idx, ids, dir) {
		if (dir < 0 && idx > 0) { return {pair: ids[idx - 1], page: ctx.page}; }
		if (dir > 0 && idx < ids.length - 1) { return {pair: ids[idx + 1], page: ctx.page}; }
		if (ctx.page < 1) { return null; }
		var adjPage = ctx.page + dir;
		if (adjPage < 1) { return null; }
		var adjIds = data.pages[adjPage];
		if (adjIds && adjIds.length) {
			return {pair: dir < 0 ? adjIds[adjIds.length - 1] : adjIds[0], page: adjPage};
		}
		return null;
	}

	function findIndex(ids, threadid) {
		for (var i = 0; i < ids.length; i++) {
			if (ids[i][0] === threadid) { return i; }
		}
		return -1;
	}

	//Common lookup used by applyLinks/applyGradingNav: returns the cached
	//page's ids array and the current thread's index within it, or null
	//if the cache doesn't cover this context/page/thread.
	function currentPageIds(ctx) {
		var data = load();
		//if (!sameContext(data, ctx) || !data.pages[ctx.page]) { return null; }
		for (let i=0; i < data.length; i++) {
			if (sameContext(data[i],ctx) && data[i].pages[ctx.page]) {
				var ids = data[i].pages[ctx.page];
				var idx = findIndex(ids, ctx.threadid);
				if (idx === -1) { return null; }
				return {data: data[i], ids: ids, idx: idx};
			}
		}
	}

	//Called by posts.php on load to populate the #prevth/#nextth links
	//from cached data. Leaves them as plain text if no usable data exists.
	function applyLinks(ctx) {
		var prevEl = document.getElementById('prevth');
		var nextEl = document.getElementById('nextth');
		if (!prevEl && !nextEl) { return; }
		var found = currentPageIds(ctx);
		if (!found) { return; }
		var data = found.data, ids = found.ids, idx = found.idx;
		var typeqs = ctx.type !== 'default' ? '&type=' + encodeURIComponent(ctx.type) : '';
		function linkFor(page, pair) {
			var pairCid = pair.length > 2 ? pair[2] : ctx.cid;
			var l = 'cid=' + encodeURIComponent(pairCid) +
				'&forum=' + encodeURIComponent(pair[1]) +
				'&page=' + encodeURIComponent(page) + typeqs;
			if (ctx.grp != null) {
				l += '&grp=' + encodeURIComponent(ctx.grp);
			}
			return l + '&thread=' + encodeURIComponent(pair[0]);
		}
		//edge= fetch target forum: for course-scoped types posts.php
		//resolves the real forumid server-side, so this is a placeholder.
		var edgeForum = isForumType(ctx.type) ? ctx.scopeid : 0;
		if (prevEl) {
			var prevNeighbor = findNeighbor(data, ctx, idx, ids, -1);
			if (prevNeighbor) {
				$(prevEl).replaceWith($("<a>", {
					id: 'prevth',
					href: 'posts.php?' + linkFor(prevNeighbor.page, prevNeighbor.pair),
					text: prevEl.textContent
				}));
			} else if (ctx.page > 1 && !ctx.tagfilter) {
				var pqs = 'cid=' + encodeURIComponent(ctx.cid) +
					'&forum=' + encodeURIComponent(edgeForum) +
					'&page=' + encodeURIComponent(ctx.page - 1) + typeqs +
					(ctx.grp != null ? '&grp=' + encodeURIComponent(ctx.grp) : '');
				$(prevEl).replaceWith($("<a>", {
					id: 'prevth',
					href: 'posts.php?' + pqs + '&edge=last',
					text: prevEl.textContent
				}));
			}
		}
		if (nextEl) {
			var nextNeighbor = findNeighbor(data, ctx, idx, ids, 1);
			if (nextNeighbor) {
				$(nextEl).replaceWith($("<a>", {
					id: 'nextth',
					href: 'posts.php?' + linkFor(nextNeighbor.page, nextNeighbor.pair),
					text: nextEl.textContent
				}));
			} else if (!ctx.tagfilter && (!data.numpages || ctx.page < data.numpages)) {
				var nqs = 'cid=' + encodeURIComponent(ctx.cid) +
					'&forum=' + encodeURIComponent(edgeForum) +
					'&page=' + encodeURIComponent(ctx.page + 1) + typeqs +
					(ctx.grp != null ? '&grp=' + encodeURIComponent(ctx.grp) : '');
				$(nextEl).replaceWith($("<a>", {
					id: 'nextth',
					href: 'posts.php?' + nqs + '&edge=first',
					text: nextEl.textContent
				}));
			}
		}
	}

	//Called by posts.php, for graded forums, to populate the hidden
	//prevth/nextth inputs and unhide the "Save Grades and View
	//Previous/Next" buttons when a cached neighbor is found. Only
	//considers directly-cached neighbors (same page, or an adjacent page
	//already in the cache) - unlike applyLinks it never offers an
	//edge=... fetch, since these are plain form-submit buttons. (In
	//practice posts.php only calls this for forum-scoped types anyway,
	//since thread.php's grading redirect can't safely follow a "next"
	//thread into a different forum.)
	function applyGradingNav(ctx) {
		var prevInput = document.getElementById('prevthinput');
		var prevBtn = document.getElementById('prevthbtn');
		var nextInput = document.getElementById('nextthinput');
		var nextBtn = document.getElementById('nextthbtn');
		if (!prevInput && !nextInput) { return; }
		var found = currentPageIds(ctx);
		if (!found) { return; }
		var data = found.data, ids = found.ids, idx = found.idx;
		var prevNeighbor = findNeighbor(data, ctx, idx, ids, -1);
		if (prevNeighbor && prevInput && prevBtn) {
			prevInput.value = prevNeighbor.pair[0];
			prevBtn.style.display = '';
		}
		var nextNeighbor = findNeighbor(data, ctx, idx, ids, 1);
		if (nextNeighbor && nextInput && nextBtn) {
			nextInput.value = nextNeighbor.pair[0];
			nextBtn.style.display = '';
		}
	}

	return {seed: seed, seedFromPage: seedFromPage, applyLinks: applyLinks, applyGradingNav: applyGradingNav};
})();
