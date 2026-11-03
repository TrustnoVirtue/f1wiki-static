( function () {
	'use strict';

	var main = document.getElementById( 'main' );
	var page = document.body.getAttribute( 'data-page' );
	var cache = {};

	function esc( value ) {
		return String( value === null || value === undefined ? '' : value ).replace( /[&<>"']/g, function ( character ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ character ];
		} );
	}

	function fetchJSON( path ) {
		if ( cache[ path ] ) {
			return cache[ path ];
		}
		cache[ path ] = fetch( path ).then( function ( response ) {
			if ( ! response.ok ) {
				throw new Error( path + ' -> ' + response.status );
			}
			return response.json();
		} );
		return cache[ path ];
	}

	function param( name ) {
		return new URLSearchParams( window.location.search ).get( name );
	}

	function points( value ) {
		var number = Number( value ) || 0;
		return number.toLocaleString( undefined, { maximumFractionDigits: 1 } );
	}

	function formatDate( iso ) {
		if ( ! iso ) {
			return '';
		}
		var parts = iso.split( '-' );
		var date = new Date( Date.UTC( parts[ 0 ], parts[ 1 ] - 1, parts[ 2 ] ) );
		return date.toLocaleDateString( undefined, { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' } );
	}

	function plural( count, singular, pluralForm ) {
		return 1 === Number( count ) ? singular : pluralForm;
	}

	function initials( name ) {
		var parts = String( name ).replace( /[^A-Za-z ]/g, '' ).trim().split( /\s+/ );
		if ( ! parts.length ) {
			return '?';
		}
		return ( parts[ 0 ][ 0 ] + ( parts.length > 1 ? parts[ parts.length - 1 ][ 0 ] : '' ) ).toUpperCase();
	}

	function teamDot( color ) {
		return '<span class="dot" style="--team-color:' + esc( color || '#999999' ) + '"></span>';
	}

	function statusPill( completed, rounds ) {
		if ( completed >= rounds && rounds > 0 ) {
			return '<span class="pill pill--done">Completed</span>';
		}
		return '<span class="pill pill--live">Round ' + esc( completed ) + ' of ' + esc( rounds ) + '</span>';
	}

	function personCell( id, name, photo, color ) {
		var media = photo
			? '<img src="' + esc( photo ) + '" alt="" loading="lazy">'
			: '<span class="mono-mark" style="--team-color:' + esc( color || '#999999' ) + '">' + esc( initials( name ) ) + '</span>';
		return '<a class="person" href="driver.html?id=' + esc( id ) + '">' + media + '<span>' + esc( name ) + '</span></a>';
	}

	function render( html ) {
		main.innerHTML = html;
	}

	function fail( message ) {
		render( '<div class="empty">' + esc( message ) + '</div>' );
	}

	function link( href, text ) {
		return '<a href="' + esc( href ) + '">' + esc( text ) + '</a>';
	}

	function loadLeader() {
		var target = document.getElementById( 'top-leader' );
		if ( ! target ) {
			return;
		}
		Promise.all( [ fetchJSON( 'data/site.json' ), fetchJSON( 'data/champions.json' ) ] ).then( function ( results ) {
			var site = results[ 0 ];
			var champions = results[ 1 ];
			var leader = champions[ champions.length - 1 ];
			target.innerHTML =
				'<span>' + esc( site.from ) + '-' + esc( site.to ) + '</span>' +
				'<span>Season <b>' + esc( site.latestSeason ) + '</b></span>' +
				( leader ? '<span>Leader <b>' + esc( leader.driver ) + '</b> ' + teamDot( leader.color ) + '</span>' : '' ) +
				'<span><a href="' + esc( 'https://github.com/TrustnoVirtue/f1wiki-static' ) + '" rel="noopener noreferrer">Source on GitHub</a></span>';
		} ).catch( function () {} );
	}

	function pageIndex() {
		Promise.all( [ fetchJSON( 'data/site.json' ), fetchJSON( 'data/champions.json' ) ] ).then( function ( results ) {
			var site = results[ 0 ];
			var champions = results[ 1 ];
			var latest = site.latestSeason;
			var season = null;
			var leader = champions[ champions.length - 1 ];

			return fetchJSON( 'data/seasons/' + latest + '.json' ).then( function ( data ) {
				season = data;

				var completed = season.completed >= season.rounds;
				var nextRace = null;
				for ( var i = 0; i < season.races.length; i++ ) {
					if ( ! season.races[ i ].hasResults ) {
						nextRace = season.races[ i ];
						break;
					}
				}

				var driversRows = season.drivers.slice( 0, 10 ).map( function ( row ) {
					return '<tr>' +
						'<td class="pos">' + esc( row.pos ) + '</td>' +
						'<td>' + personCell( row.driverId, row.name, row.photo, row.color ) + '</td>' +
						'<td>' + teamDot( row.color ) + ' ' + esc( row.team ) + '</td>' +
						'<td class="num">' + esc( row.wins ) + '</td>' +
						'<td class="num"><b>' + points( row.points ) + '</b></td>' +
						'</tr>';
				} ).join( '' );

				var teamsRows = season.teams.slice( 0, 10 ).map( function ( row ) {
					return '<tr>' +
						'<td class="pos">' + esc( row.pos ) + '</td>' +
						'<td>' + teamDot( row.color ) + ' ' + link( 'team.html?id=' + row.teamId, row.name ) + '</td>' +
						'<td class="num">' + esc( row.wins ) + '</td>' +
						'<td class="num"><b>' + points( row.points ) + '</b></td>' +
						'</tr>';
				} ).join( '' );

				var championsRows = champions.slice().reverse().map( function ( champion ) {
					return '<tr>' +
						'<td class="pos">' + esc( champion.year ) + '</td>' +
						'<td>' + link( 'driver.html?id=' + champion.driverId, champion.driver ) + '</td>' +
						'<td>' + teamDot( champion.color ) + ' ' + link( 'team.html?id=' + champion.teamId, champion.team ) + '</td>' +
						'<td class="num">' + points( champion.points ) + '</td>' +
						'</tr>';
				} ).join( '' );

				var recent = season.races.filter( function ( race ) {
					return race.hasResults;
				} ).slice( -6 ).reverse().map( function ( race ) {
					return '<tr>' +
						'<td class="pos">R' + esc( race.round ) + '</td>' +
						'<td>' + link( 'race.html?id=' + race.id, race.name.replace( /^\d{4}\s+/, '' ) ) + '</td>' +
						'<td>' + esc( formatDate( race.date ) ) + '</td>' +
						'<td>' + teamDot( race.winnerColor ) + ' ' + esc( race.winner || '—' ) + '</td>' +
						'</tr>';
				} ).join( '' );

				render(
					'<section class="dash-hero">' +
						'<div class="dash-hero__copy">' +
							'<p class="eyebrow">' + esc( site.from ) + '-' + esc( site.to ) + ' archive</p>' +
							'<h1>Every result.<br><em>One archive.</em></h1>' +
							'<p>Drivers, teams, circuits and every championship from ' + esc( site.from ) + ' to ' + esc( site.to ) + ', generated from the Jolpica F1 API.</p>' +
							'<div class="dash-stats">' +
								'<div><strong>' + esc( site.counts.races ) + '</strong><span>races</span></div>' +
								'<div><strong>' + esc( site.counts.drivers ) + '</strong><span>drivers</span></div>' +
								'<div><strong>' + esc( site.counts.teams ) + '</strong><span>teams</span></div>' +
								'<div><strong>' + points( site.counts.results ) + '</strong><span>results</span></div>' +
							'</div>' +
						'</div>' +
						'<aside class="snapshot" style="--team-color:' + esc( leader ? leader.color : '#E10600' ) + '">' +
							'<div class="snapshot__head">' +
								'<span class="snapshot__year">' + esc( latest ) + '</span>' +
								statusPill( season.completed, season.rounds ) +
							'</div>' +
							'<div class="snapshot__rows">' +
								( leader ? '<div class="snapshot__row"><span>' + ( completed ? 'Champion' : 'Championship leader' ) + '</span><span>' + personCell( leader.driverId, leader.driver, null, leader.color ) + '</span></div>' : '' ) +
								( season.teamChampion ? '<div class="snapshot__row"><span>Constructors</span><span>' + teamDot( season.teamChampion.color ) + ' ' + esc( season.teamChampion.name ) + '</span></div>' : '' ) +
								( nextRace ? '<div class="snapshot__row"><span>Next race</span><span>' + link( 'race.html?id=' + nextRace.id, nextRace.name.replace( /^\d{4}\s+/, '' ) ) + ' · ' + esc( formatDate( nextRace.date ) ) + '</span></div>' : '' ) +
							'</div>' +
						'</aside>' +
					'</section>' +
					'<section class="section">' +
						'<div class="section__head"><h2>' + esc( latest ) + ' standings</h2>' + link( 'season.html?year=' + latest, 'Full season' ) + '</div>' +
						'<div class="columns">' +
							'<div class="panel"><div class="panel__head"><h3>Drivers</h3><span>Top 10</span></div><table class="tbl"><thead><tr><th class="pos">Pos</th><th>Driver</th><th>Team</th><th class="num">Wins</th><th class="num">Pts</th></tr></thead><tbody>' + driversRows + '</tbody></table></div>' +
							'<div class="panel"><div class="panel__head"><h3>Constructors</h3><span>Top 10</span></div><table class="tbl"><thead><tr><th class="pos">Pos</th><th>Team</th><th class="num">Wins</th><th class="num">Pts</th></tr></thead><tbody>' + teamsRows + '</tbody></table></div>' +
						'</div>' +
					'</section>' +
					'<section class="section">' +
						'<div class="section__head"><h2>World champions</h2>' + link( 'champions.html', 'Full roll of honour' ) + '</div>' +
						'<div class="panel"><table class="tbl"><thead><tr><th class="pos">Year</th><th>Champion</th><th>Team</th><th class="num">Points</th></tr></thead><tbody>' + championsRows + '</tbody></table></div>' +
					'</section>' +
					'<section class="section">' +
						'<div class="section__head"><h2>Latest races</h2>' + link( 'races.html', 'All races' ) + '</div>' +
						'<div class="panel"><table class="tbl"><thead><tr><th class="pos">Round</th><th>Race</th><th>Date</th><th>Winner</th></tr></thead><tbody>' + recent + '</tbody></table></div>' +
					'</section>'
				);
			} );
		} ).catch( function ( error ) {
			fail( 'Could not load the archive: ' + error.message );
		} );
	}

	function pageChampions() {
		fetchJSON( 'data/champions.json' ).then( function ( champions ) {
			var rows = champions.slice().reverse().map( function ( champion ) {
				return '<tr>' +
					'<td class="pos">' + esc( champion.year ) + '</td>' +
					'<td>' + link( 'driver.html?id=' + champion.driverId, champion.driver ) + '</td>' +
					'<td>' + teamDot( champion.color ) + ' ' + link( 'team.html?id=' + champion.teamId, champion.team ) + '</td>' +
					'<td class="num">' + points( champion.points ) + '</td>' +
					'</tr>';
			} ).join( '' );

			render(
				'<div class="page-head"><p class="eyebrow">Roll of honour</p><h1>World champions ' + champions[ 0 ].year + '-' + champions[ champions.length - 1 ].year + '</h1></div>' +
				'<div class="panel"><table class="tbl"><thead><tr><th class="pos">Year</th><th>Champion</th><th>Team</th><th class="num">Points</th></tr></thead><tbody>' + rows + '</tbody></table></div>'
			);
		} ).catch( function ( error ) {
			fail( error.message );
		} );
	}

	function pageDrivers() {
		Promise.all( [ fetchJSON( 'data/drivers.json' ), fetchJSON( 'data/teams.json' ) ] ).then( function ( results ) {
			var drivers = results[ 0 ];
			var teams = results[ 1 ];
			var teamOptions = teams.map( function ( team ) {
				return '<option value="' + team.id + '">' + esc( team.name ) + '</option>';
			} ).join( '' );

			render(
				'<div class="page-head"><p class="eyebrow">Archive</p><h1>Drivers</h1><p class="lede">' + drivers.length + ' drivers with career totals, team history and results.</p></div>' +
				'<div class="filters">' +
					'<input type="search" id="driver-q" placeholder="Search drivers…" autocomplete="off">' +
					'<select id="driver-team"><option value="">All teams</option>' + teamOptions + '</select>' +
				'</div>' +
				'<p class="search-note" id="driver-count"></p>' +
				'<div class="tiles" id="driver-tiles"></div>'
			);

			var grid = document.getElementById( 'driver-tiles' );
			var count = document.getElementById( 'driver-count' );
			var query = document.getElementById( 'driver-q' );
			var teamSelect = document.getElementById( 'driver-team' );

			function draw() {
				var needle = query.value.trim().toLowerCase();
				var team = teamSelect.value;
				var filtered = drivers.filter( function ( driver ) {
					if ( needle && driver.name.toLowerCase().indexOf( needle ) === -1 ) {
						return false;
					}
					if ( team && String( driver.teamId ) !== team ) {
						return false;
					}
					return true;
				} );

				count.textContent = filtered.length + ' ' + plural( filtered.length, 'driver', 'drivers' );

				grid.innerHTML = filtered.map( function ( driver ) {
					var media = driver.photo
						? '<img class="tile__photo" src="' + esc( driver.photo ) + '" alt="" loading="lazy" style="--team-color:' + esc( driver.color ) + '">'
						: '<span class="mono-mark" style="--team-color:' + esc( driver.color ) + ';width:84px;height:84px;font-size:18px">' + esc( driver.number || initials( driver.name ) ) + '</span>';
					return '<a class="tile" href="driver.html?id=' + driver.id + '" style="--team-color:' + esc( driver.color ) + '">' +
						media +
						'<h3>' + esc( driver.name ) + '</h3>' +
						'<p>' + teamDot( driver.color ) + ' ' + esc( driver.teamName || 'Formula 1' ) + '</p>' +
						'<div class="tile__meta"><b>' + esc( driver.stats.wins ) + '</b> ' + plural( driver.stats.wins, 'win', 'wins' ) +
						' · <b>' + esc( driver.stats.championships ) + '</b> ' + plural( driver.stats.championships, 'title', 'titles' ) +
						' · ' + esc( driver.firstSeason ) + '-' + esc( driver.lastSeason ) + '</div></a>';
				} ).join( '' );

				if ( ! filtered.length ) {
					grid.innerHTML = '<div class="empty">No drivers match that search.</div>';
				}
			}

			query.addEventListener( 'input', draw );
			teamSelect.addEventListener( 'change', draw );
			draw();
		} ).catch( function ( error ) {
			fail( error.message );
		} );
	}

	function pageDriver() {
		var id = param( 'id' );
		if ( ! id ) {
			fail( 'No driver selected.' );
			return;
		}

		fetchJSON( 'data/drivers/' + id + '.json' ).then( function ( data ) {
			var driver = data.driver;
			var stats = driver.stats;

			var media = driver.photo
				? '<img src="' + esc( driver.photo ) + '" alt="">'
				: '<div style="padding:30px;text-align:center"><span class="mono-mark" style="--team-color:' + esc( driver.color ) + ';width:120px;height:120px;font-size:30px">' + esc( driver.number || initials( driver.name ) ) + '</span></div>';

			var credit = driver.credit
				? '<p class="profile__credit">' + ( driver.creditUrl ? '<a href="' + esc( driver.creditUrl ) + '" rel="noopener noreferrer">' + esc( driver.credit ) + '</a>' : esc( driver.credit ) ) + '</p>'
				: '';

			var seasons = data.seasons.map( function ( season ) {
				return '<tr>' +
					'<td class="pos">' + esc( season.year ) + '</td>' +
					'<td>' + teamDot( season.color ) + ' ' + link( 'team.html?id=' + season.teamId, season.team ) + '</td>' +
					'<td class="num">P' + esc( season.pos ) + '</td>' +
					'<td class="num">' + esc( season.wins ) + '</td>' +
					'<td class="num"><b>' + points( season.points ) + '</b></td>' +
					'</tr>';
			} ).join( '' );

			var recent = data.recent.map( function ( row ) {
				return '<tr>' +
					'<td class="pos">' + esc( row.pos ) + '</td>' +
					'<td>' + link( 'race.html?id=' + row.raceId, row.race.replace( /^\d{4}\s+/, '' ) ) + '</td>' +
					'<td>' + esc( row.year ) + '</td>' +
					'<td>' + esc( row.team ) + '</td>' +
					'<td>' + esc( row.status ) + '</td>' +
					'<td class="num">' + ( row.points ? points( row.points ) : '—' ) + '</td>' +
					'</tr>';
			} ).join( '' );

			render(
				'<section class="profile" style="--team-color:' + esc( driver.color ) + '">' +
					'<div><div class="profile__media">' + media + '</div>' + credit + '</div>' +
					'<div>' +
						'<p class="eyebrow">Driver profile</p>' +
						'<h1>' + esc( driver.name ) + '</h1>' +
						'<p class="oneliner">' + esc( driver.nat ) + ' · ' + ( driver.teamName ? esc( driver.teamName ) : 'Formula 1' ) + ( driver.number ? ' · Car ' + esc( driver.number ) : '' ) + ( driver.code ? ' · ' + esc( driver.code ) : '' ) + '</p>' +
						'<div class="profile__facts">' +
							'<div><span>Championships</span><strong>' + esc( stats.championships ) + '</strong></div>' +
							'<div><span>Wins</span><strong>' + esc( stats.wins ) + '</strong></div>' +
							'<div><span>Podiums</span><strong>' + esc( stats.podiums ) + '</strong></div>' +
							'<div><span>Pole positions</span><strong>' + esc( stats.poles ) + '</strong></div>' +
							'<div><span>Fastest laps</span><strong>' + esc( stats.fastestLaps ) + '</strong></div>' +
							'<div><span>Starts</span><strong>' + esc( stats.starts ) + '</strong></div>' +
							'<div><span>Career points</span><strong>' + points( stats.points ) + '</strong></div>' +
							'<div><span>Best championship</span><strong>' + ( stats.best ? 'P' + esc( stats.best ) + ( stats.bestYear ? ' · ' + esc( stats.bestYear ) : '' ) : '—' ) + '</strong></div>' +
							'<div><span>Archive span</span><strong>' + esc( driver.firstSeason ) + '-' + esc( driver.lastSeason ) + '</strong></div>' +
						'</div>' +
					'</div>' +
				'</section>' +
				'<section class="section"><div class="section__head"><h2>Season by season</h2></div>' +
					'<div class="panel"><table class="tbl"><thead><tr><th class="pos">Season</th><th>Team</th><th class="num">Pos</th><th class="num">Wins</th><th class="num">Points</th></tr></thead><tbody>' + seasons + '</tbody></table></div></section>' +
				'<section class="section"><div class="section__head"><h2>Recent results</h2></div>' +
					'<div class="panel"><table class="tbl"><thead><tr><th class="pos">Pos</th><th>Race</th><th>Year</th><th>Team</th><th>Status</th><th class="num">Pts</th></tr></thead><tbody>' + recent + '</tbody></table></div></section>'
			);
		} ).catch( function ( error ) {
			fail( error.message );
		} );
	}

	function pageTeams() {
		fetchJSON( 'data/teams.json' ).then( function ( teams ) {
			var tiles = teams.map( function ( team ) {
				var media = team.logo
					? '<img class="tile__logo" src="' + esc( team.logo ) + '" alt="" loading="lazy">'
					: '<span class="tile__mark" style="--team-color:' + esc( team.color ) + '">' + esc( initials( team.name ) ) + '</span>';
				return '<a class="tile" href="team.html?id=' + team.id + '" style="--team-color:' + esc( team.color ) + '">' +
					media +
					'<h3>' + esc( team.name ) + '</h3>' +
					'<p>' + esc( team.nat || 'Constructor' ) + '</p>' +
					'<div class="tile__meta"><b>' + esc( team.stats.championships ) + '</b> ' + plural( team.stats.championships, 'title', 'titles' ) +
					' · <b>' + esc( team.stats.wins ) + '</b> ' + plural( team.stats.wins, 'win', 'wins' ) +
					' · ' + esc( team.firstSeason ) + '-' + esc( team.lastSeason ) + '</div></a>';
			} ).join( '' );

			render(
				'<div class="page-head"><p class="eyebrow">Archive</p><h1>Teams</h1><p class="lede">' + teams.length + ' constructors with championship years, wins and line-ups.</p></div>' +
				'<div class="tiles">' + tiles + '</div>'
			);
		} ).catch( function ( error ) {
			fail( error.message );
		} );
	}

	function pageTeam() {
		var id = param( 'id' );
		if ( ! id ) {
			fail( 'No team selected.' );
			return;
		}

		fetchJSON( 'data/teams/' + id + '.json' ).then( function ( data ) {
			var team = data.team;
			var stats = team.stats;

			var media = team.logo
				? '<img class="is-logo" src="' + esc( team.logo ) + '" alt="">'
				: '<div style="padding:30px;text-align:center"><span class="tile__mark" style="--team-color:' + esc( team.color ) + ';min-height:120px;font-size:26px">' + esc( initials( team.name ) ) + '</span></div>';

			var credit = team.credit
				? '<p class="profile__credit">' + ( team.creditUrl ? '<a href="' + esc( team.creditUrl ) + '" rel="noopener noreferrer">' + esc( team.credit ) + '</a>' : esc( team.credit ) ) + '</p>'
				: '';

			var seasons = data.seasons.map( function ( season ) {
				return '<tr>' +
					'<td class="pos">' + esc( season.year ) + '</td>' +
					'<td class="num">P' + esc( season.pos ) + '</td>' +
					'<td class="num">' + esc( season.wins ) + '</td>' +
					'<td class="num"><b>' + points( season.points ) + '</b></td>' +
					'</tr>';
			} ).join( '' );

			var wins = data.wins.map( function ( win ) {
				return '<tr>' +
					'<td class="pos">' + esc( win.year ) + '</td>' +
					'<td>' + link( 'race.html?id=' + win.raceId, win.race.replace( /^\d{4}\s+/, '' ) ) + '</td>' +
					'<td>' + link( 'driver.html?id=' + win.driverId, win.driver ) + '</td>' +
					'</tr>';
			} ).join( '' );

			var lineup = data.lineup.map( function ( driver ) {
				var photo = driver.photo
					? '<img src="' + esc( driver.photo ) + '" alt="" loading="lazy">'
					: '<span class="mono-mark" style="--team-color:' + esc( team.color ) + '">' + esc( initials( driver.name ) ) + '</span>';
				return '<tr><td><a class="person" href="driver.html?id=' + driver.id + '">' + photo + '<span>' + esc( driver.name ) + '</span></a></td></tr>';
			} ).join( '' );

			render(
				'<section class="profile" style="--team-color:' + esc( team.color ) + '">' +
					'<div><div class="profile__media">' + media + '</div>' + credit + '</div>' +
					'<div>' +
						'<p class="eyebrow">Constructor profile</p>' +
						'<h1>' + esc( team.name ) + '</h1>' +
						'<p class="oneliner">' + esc( team.nat || 'Constructor' ) + ' · ' + esc( team.firstSeason ) + '-' + esc( team.lastSeason ) + ' in the archive</p>' +
						'<div class="profile__facts">' +
							'<div><span>Championships</span><strong>' + esc( stats.championships ) + '</strong></div>' +
							'<div><span>Wins</span><strong>' + esc( stats.wins ) + '</strong></div>' +
							'<div><span>Podiums</span><strong>' + esc( stats.podiums ) + '</strong></div>' +
							'<div><span>Pole positions</span><strong>' + esc( stats.poles ) + '</strong></div>' +
							'<div><span>Starts</span><strong>' + esc( stats.starts ) + '</strong></div>' +
							'<div><span>Points</span><strong>' + points( stats.points ) + '</strong></div>' +
						'</div>' +
					'</div>' +
				'</section>' +
				( lineup ? '<section class="section"><div class="section__head"><h2>Latest line-up</h2></div><div class="panel"><table class="tbl"><tbody>' + lineup + '</tbody></table></div></section>' : '' ) +
				'<section class="section"><div class="section__head"><h2>Season by season</h2></div>' +
					'<div class="panel"><table class="tbl"><thead><tr><th class="pos">Season</th><th class="num">Pos</th><th class="num">Wins</th><th class="num">Points</th></tr></thead><tbody>' + seasons + '</tbody></table></div></section>' +
				'<section class="section"><div class="section__head"><h2>Recent wins</h2></div>' +
					'<div class="panel"><table class="tbl"><thead><tr><th class="pos">Year</th><th>Race</th><th>Driver</th></tr></thead><tbody>' + wins + '</tbody></table></div></section>'
			);
		} ).catch( function ( error ) {
			fail( error.message );
		} );
	}

	function pageSeasons() {
		fetchJSON( 'data/seasons.json' ).then( function ( seasons ) {
			var rows = seasons.map( function ( season ) {
				return '<tr>' +
					'<td class="pos">' + link( 'season.html?year=' + season.year, season.year ) + '</td>' +
					'<td>' + link( 'driver.html?id=' + season.champion.driverId, season.champion.driver ) + '</td>' +
					'<td>' + teamDot( season.champion.color ) + ' ' + link( 'team.html?id=' + season.champion.teamId, season.champion.team ) + '</td>' +
					'<td>' + ( season.teamChampion ? teamDot( season.teamChampion.color ) + ' ' + esc( season.teamChampion.name ) : '—' ) + '</td>' +
					'<td class="num">' + esc( season.completed ) + '/' + esc( season.rounds ) + '</td>' +
					'<td>' + ( season.completed >= season.rounds ? '<span class="pill pill--done">Done</span>' : '<span class="pill pill--live">Live</span>' ) + '</td>' +
					'</tr>';
			} ).join( '' );

			render(
				'<div class="page-head"><p class="eyebrow">Archive</p><h1>Seasons</h1></div>' +
				'<div class="panel"><table class="tbl"><thead><tr><th class="pos">Year</th><th>Champion</th><th>Team</th><th>Constructors</th><th class="num">Rounds</th><th>Status</th></tr></thead><tbody>' + rows + '</tbody></table></div>'
			);
		} ).catch( function ( error ) {
			fail( error.message );
		} );
	}

	function pageSeason() {
		var year = param( 'year' );
		fetchJSON( 'data/site.json' ).then( function ( site ) {
			year = year || site.latestSeason;
			return fetchJSON( 'data/seasons/' + year + '.json' );
		} ).then( function ( season ) {
			var driversRows = season.drivers.map( function ( row ) {
				return '<tr>' +
					'<td class="pos">' + esc( row.pos ) + '</td>' +
					'<td>' + personCell( row.driverId, row.name, row.photo, row.color ) + '</td>' +
					'<td>' + teamDot( row.color ) + ' ' + esc( row.team ) + '</td>' +
					'<td class="num">' + esc( row.wins ) + '</td>' +
					'<td class="num"><b>' + points( row.points ) + '</b></td>' +
					'</tr>';
			} ).join( '' );

			var teamsRows = season.teams.map( function ( row ) {
				return '<tr>' +
					'<td class="pos">' + esc( row.pos ) + '</td>' +
					'<td>' + teamDot( row.color ) + ' ' + link( 'team.html?id=' + row.teamId, row.name ) + '</td>' +
					'<td class="num">' + esc( row.wins ) + '</td>' +
					'<td class="num"><b>' + points( row.points ) + '</b></td>' +
					'</tr>';
			} ).join( '' );

			var calendar = season.races.map( function ( race ) {
				return '<tr>' +
					'<td class="pos">' + esc( race.round ) + '</td>' +
					'<td>' + link( 'race.html?id=' + race.id, race.name.replace( /^\d{4}\s+/, '' ) ) + '</td>' +
					'<td>' + esc( formatDate( race.date ) ) + '</td>' +
					'<td>' + esc( race.circuit ) + '</td>' +
					'<td>' + ( race.hasResults ? teamDot( race.winnerColor ) + ' ' + esc( race.winner ) : '<span class="pill">Scheduled</span>' ) + '</td>' +
					'</tr>';
			} ).join( '' );

			render(
				'<div class="page-head"><p class="eyebrow">Season</p><h1>' + esc( season.year ) + ' Formula One season</h1>' +
					'<p class="lede">' + esc( season.completed ) + ' of ' + esc( season.rounds ) + ' rounds completed' +
					( season.champion ? ' · Leader ' + esc( season.champion.driver ) : '' ) + '</p>' + statusPill( season.completed, season.rounds ) + '</div>' +
				'<section class="section"><div class="section__head"><h2>Drivers championship</h2></div>' +
					'<div class="panel"><table class="tbl"><thead><tr><th class="pos">Pos</th><th>Driver</th><th>Team</th><th class="num">Wins</th><th class="num">Pts</th></tr></thead><tbody>' + driversRows + '</tbody></table></div></section>' +
				'<section class="section"><div class="section__head"><h2>Constructors championship</h2></div>' +
					'<div class="panel"><table class="tbl"><thead><tr><th class="pos">Pos</th><th>Team</th><th class="num">Wins</th><th class="num">Pts</th></tr></thead><tbody>' + teamsRows + '</tbody></table></div></section>' +
				'<section class="section"><div class="section__head"><h2>Calendar</h2></div>' +
					'<div class="panel"><table class="tbl"><thead><tr><th class="pos">R</th><th>Race</th><th>Date</th><th>Circuit</th><th>Winner</th></tr></thead><tbody>' + calendar + '</tbody></table></div></section>'
			);
		} ).catch( function ( error ) {
			fail( error.message );
		} );
	}

	function pageRaces() {
		var year = param( 'year' );
		fetchJSON( 'data/seasons.json' ).then( function ( seasons ) {
			year = year || seasons[ 0 ].year;
			var options = seasons.map( function ( season ) {
				return '<option value="' + season.year + '"' + ( String( season.year ) === String( year ) ? ' selected' : '' ) + '>' + season.year + '</option>';
			} ).join( '' );

			return fetchJSON( 'data/seasons/' + year + '.json' ).then( function ( season ) {
				var rows = season.races.map( function ( race ) {
					return '<tr>' +
						'<td class="pos">' + esc( race.round ) + '</td>' +
						'<td>' + link( 'race.html?id=' + race.id, race.name.replace( /^\d{4}\s+/, '' ) ) + '</td>' +
						'<td>' + esc( formatDate( race.date ) ) + '</td>' +
						'<td>' + esc( race.circuit ) + '</td>' +
						'<td>' + ( race.hasResults ? teamDot( race.winnerColor ) + ' ' + esc( race.winner ) : '<span class="pill">Scheduled</span>' ) + '</td>' +
						'</tr>';
				} ).join( '' );

				render(
					'<div class="page-head"><p class="eyebrow">Calendar</p><h1>Races</h1></div>' +
					'<div class="filters"><select id="race-year">' + options + '</select></div>' +
					'<div class="panel"><table class="tbl"><thead><tr><th class="pos">R</th><th>Race</th><th>Date</th><th>Circuit</th><th>Winner</th></tr></thead><tbody>' + rows + '</tbody></table></div>'
				);

				document.getElementById( 'race-year' ).addEventListener( 'change', function ( event ) {
					window.location.search = '?year=' + event.target.value;
				} );
			} );
		} ).catch( function ( error ) {
			fail( error.message );
		} );
	}

	function pageRace() {
		var id = param( 'id' );
		if ( ! id ) {
			fail( 'No race selected.' );
			return;
		}

		fetchJSON( 'data/races/' + id + '.json' ).then( function ( race ) {
			var rows = race.results.map( function ( row ) {
				var classes = [];
				if ( '1' === String( row.pos ) ) {
					classes.push( 'win-row' );
				}
				if ( row.fastest ) {
					classes.push( 'fastest' );
				}
				return '<tr' + ( classes.length ? ' class="' + classes.join( ' ' ) + '"' : '' ) + '>' +
					'<td class="pos">' + esc( row.pos ) + '</td>' +
					'<td class="num">' + esc( row.car ) + '</td>' +
					'<td>' + personCell( row.driverId, row.driver, null, row.color ) + '</td>' +
					'<td>' + teamDot( row.color ) + ' ' + esc( row.team ) + '</td>' +
					'<td class="num">' + ( row.grid ? 'P' + esc( row.grid ) : '—' ) + '</td>' +
					'<td class="num">' + esc( row.laps ) + '</td>' +
					'<td>' + esc( row.status ) + ( row.fastest ? ' <span class="pill pill--done">FL</span>' : '' ) + '</td>' +
					'<td class="num">' + ( row.points ? points( row.points ) : '—' ) + '</td>' +
					'</tr>';
			} ).join( '' );

			render(
				'<div class="page-head"><p class="eyebrow">' + esc( race.year ) + ' season · Round ' + esc( race.round ) + '</p>' +
					'<h1>' + esc( race.name ) + '</h1>' +
					'<p class="lede">' + esc( formatDate( race.date ) ) + ' · ' + esc( race.circuit ) + ( race.country ? ', ' + esc( race.country ) : '' ) + '</p></div>' +
				'<div class="columns" style="margin-bottom:26px">' +
					'<div class="snapshot" style="--team-color:' + esc( race.winnerColor ) + '"><div class="snapshot__rows">' +
						'<div class="snapshot__row"><span>Winner</span><span>' + ( race.winner ? link( 'driver.html?id=' + race.winnerId, race.winner ) + ' · ' + esc( race.winnerTeam ) : '—' ) + '</span></div>' +
						'<div class="snapshot__row"><span>Pole position</span><span>' + ( race.pole ? link( 'driver.html?id=' + race.poleId, race.pole ) : '—' ) + '</span></div>' +
						'<div class="snapshot__row"><span>Fastest lap</span><span>' + ( race.fastest ? link( 'driver.html?id=' + race.fastestId, race.fastest ) : '—' ) + '</span></div>' +
					'</div></div>' +
				'</div>' +
				( race.results.length
					? '<section class="section"><div class="section__head"><h2>Classification</h2></div>' +
						'<div class="panel"><table class="tbl"><thead><tr><th class="pos">Pos</th><th class="num">No</th><th>Driver</th><th>Team</th><th class="num">Grid</th><th class="num">Laps</th><th>Status</th><th class="num">Pts</th></tr></thead><tbody>' + rows + '</tbody></table></div></section>'
					: '<div class="empty">This race has not been run yet.</div>' )
			);
		} ).catch( function ( error ) {
			fail( error.message );
		} );
	}

	function pageCircuits() {
		fetchJSON( 'data/circuits.json' ).then( function ( circuits ) {
			var rows = circuits.map( function ( circuit ) {
				return '<tr>' +
					'<td>' + esc( circuit.name ) + '</td>' +
					'<td>' + esc( circuit.locality ) + '</td>' +
					'<td>' + esc( circuit.country ) + '</td>' +
					'<td class="num">' + esc( circuit.races ) + '</td>' +
					'<td class="num">' + esc( circuit.firstSeason ) + '-' + esc( circuit.lastSeason ) + '</td>' +
					'</tr>';
			} ).join( '' );

			render(
				'<div class="page-head"><p class="eyebrow">Venues</p><h1>Circuits</h1></div>' +
				'<div class="panel"><table class="tbl"><thead><tr><th>Circuit</th><th>Locality</th><th>Country</th><th class="num">Races</th><th class="num">Span</th></tr></thead><tbody>' + rows + '</tbody></table></div>'
			);
		} ).catch( function ( error ) {
			fail( error.message );
		} );
	}

	function toggleNav() {
		var button = document.querySelector( '.nav-toggle' );
		var sidebar = document.getElementById( 'sidebar' );
		if ( ! button || ! sidebar ) {
			return;
		}
		button.addEventListener( 'click', function () {
			var open = sidebar.classList.toggle( 'is-open' );
			button.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		} );
	}

	var routes = {
		index: pageIndex,
		champions: pageChampions,
		drivers: pageDrivers,
		driver: pageDriver,
		teams: pageTeams,
		team: pageTeam,
		seasons: pageSeasons,
		season: pageSeason,
		races: pageRaces,
		race: pageRace,
		circuits: pageCircuits,
		'404': function () {
			render( '<div class="page-head"><p class="eyebrow">404</p><h1>That page is off the grid.</h1><p class="lede">Try the ' + link( 'index.html', 'dashboard' ) + ', ' + link( 'drivers.html', 'drivers' ) + ' or ' + link( 'races.html', 'races' ) + '.</p></div>' );
		}
	};

	toggleNav();
	loadLeader();

	if ( routes[ page ] ) {
		routes[ page ]();
	} else {
		routes.index();
	}
} )();
