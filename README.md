# F1 Archive

A static Formula 1 archive for the 2000–2026 seasons, published with GitHub Pages.
It is a separate, self-contained website: the WordPress build it was generated from stays local.

**Live site:** https://trustnovirtue.github.io/f1wiki-static/

## What is in here

| Path | Contents |
| --- | --- |
| `index.html` | Dashboard: season snapshot, standings, champions, latest races |
| `champions.html` | Every world champion from 2000 to 2026 |
| `drivers.html`, `driver.html?id=` | Driver index and profiles |
| `teams.html`, `team.html?id=` | Constructor index and profiles |
| `seasons.html`, `season.html?year=` | Season standings and calendars |
| `races.html`, `race.html?id=` | Calendars and full race classifications |
| `circuits.html` | Circuit index |
| `data/` | JSON generated from the local database |
| `assets/` | JetBrains Mono Nerd Font, CSS, JS, driver photos, team logos |
| `tools/build.php` | Generator that produced this site from the local WordPress database |
| `wordpress/` | Source of the local WordPress theme and plugin (no fonts, no database) |

## Pages

- Drivers: 131
- Teams: 40
- Circuits: 39
- Races: 526
- Seasons: 27
- Result rows: 10,968

## Rebuilding

The generator runs against the local XAMPP database and writes the whole site:

```powershell
C:\xampp\php\php.exe tools\build.php
```

It reads `wp_f1_results`, `wp_f1_driver_standings`, `wp_f1_team_standings` and the
`driver`, `team`, `circuit` and `race` posts, exports JSON, resizes photos and logos,
and rewrites the HTML shells. Detail pages render client-side from the JSON files.

Then publish the changes:

```powershell
git add -A
git commit -m "Rebuild static archive"
git push
```

## Data and attribution

- Race data: [Jolpica F1 API](https://api.jolpi.ca/ergast/f1/) (Ergast compatible, open data).
- Driver portraits and team logos: Wikipedia / Wikimedia Commons under their free
  licenses. Each image keeps its photographer and license credit in the JSON and on
  the profile pages.
- Statistics describe the 2000–2026 archive, not complete career totals. The source
  archive does not record fastest laps for 2000–2003, so those totals begin in 2004.
- Not affiliated with Formula 1, the FIA or any team.

## WordPress

The WordPress edition of this project continues to live at `http://localhost/f1wiki/`.
Its theme and plugin source is included under `wordpress/` for reference; it is not
built or deployed here.
