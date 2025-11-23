<?php
    include 'api.php';

    function get_db(): mysqli {
        mysqli_report(MYSQLI_REPORT_ALL & ~MYSQLI_REPORT_INDEX);

        $databaseHost = 'localhost';
        $databaseUsername = 'root';
        $databasePassword = 'SqlPassword';
        $databaseName = 'paris_l1';
        return mysqli_connect($databaseHost, $databaseUsername, $databasePassword, $databaseName);
    }

    function query($query, $types = "", ...$vars): false|mysqli_result {
        $db = get_db();

        $stmt = mysqli_stmt_init($db);
        mysqli_stmt_prepare($stmt, $query);

        if ($types != "") {
            $parameters = [];
            foreach ($vars as $value) {
                if (is_string($value)) {
                    $parameters[] = mysqli_real_escape_string($db, $value);
                } else {
                    $parameters[] = $value;
                }
            }
            mysqli_stmt_bind_param($stmt, $types, ...$parameters);
        }

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $stmt->close();
        $db->close();
        return $result;
    }

    function get_match_status($match): string {
        if ($match['strStatus'] === 'Match Finished') {
            return "FINISHED";
        } else {
            $deltaTime = time() - strtotime($match['strTimestamp']);
            if ($deltaTime < 0) {
                return "READY";
            } else {
                return "ONGOING";
            }
        }
    }

    function insert_match($match): void
    {
        $match_status = get_match_status($match);
        $journee = 0; // TODO calculer la journee de ligue 1

        check_team($match["strHomeTeam"]);

        check_team($match["strAwayTeam"]);

        $match["intHomeScore"] = $match["intHomeScore"] === null ? 0 : $match["intHomeScore"];
        $match["intAwayScore"] = $match["intAwayScore"] === null ? 0 : $match["intAwayScore"];

        query(
            "INSERT INTO `MATCH` VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)",
            "iiiiisiss",
            $match["idEvent"], $match["idHomeTeam"], $match["idAwayTeam"],
            $match["intHomeScore"], $match["intAwayScore"], $match["strTimestamp"],
            $journee, $match_status,
            date("Y-m-d H:i:s")
        );
    }

    function update_match($match): void
    {
        $match_status = get_match_status($match);

        $match["intHomeScore"] = $match["intHomeScore"] === null ? 0 : $match["intHomeScore"];
        $match["intAwayScore"] = $match["intAwayScore"] === null ? 0 : $match["intAwayScore"];

        query(
            "UPDATE `MATCH` SET score_equipe1=?, score_equipe2=?, status=?, date_update=? WHERE id = ?",
            "iissi",
            $match["intHomeScore"], $match["intAwayScore"], $match_status, date("Y-m-d H:i:s"), $match["idEvent"]
        );
    }

    function insert_update_match($matches): void
    {
        foreach ($matches as $match) {
            $result = query("SELECT id FROM `MATCH` WHERE id = ?", "i", $match['idEvent']);

            if ($result->num_rows == 0) {
                insert_match($match);
            } else {
                update_match($match);
            }
        }
    }

    function check_team($team_name): void
    {
        // Check si dans la table
        $result = query("SELECT id FROM `EQUIPE` WHERE nom = ?", "s", $team_name);
        if ($result->num_rows != 0) { return; }

        // Si il n'y est pas, insert
        $team_info = get_API("searchteams.php?t=$team_name")['teams'][0];
        $badge_url = $team_info['strBadge'];
        query(
            "INSERT INTO `EQUIPE` (id, nom, image_url) VALUES (?, ?, ?)",
            "iss",
            $team_info["idTeam"], $team_info["strTeam"], $badge_url
        );
    }

    function check_match(): void {
        $q = query("SELECT date_update FROM `MATCH` ORDER BY date_update DESC LIMIT 1");
        $last_time_row = $q->fetch_assoc();

        if ($last_time_row && time() - strtotime($last_time_row["date_update"]) < 60 ) { return; }

        $league_id = get_league_id();
        $base_select_query = "SELECT * FROM `MATCH` WHERE ";

        $result = query($base_select_query."status != ?", "s", "FINISHED");
        foreach ($result->fetch_all(MYSQLI_ASSOC) as $match) {
            $match_id = $match["id"];
            $match = get_API("lookupevent.php?id=$match_id")["events"][0];
            update_match($match);
        }

        $next_match = get_API("eventsnextleague.php?id=$league_id")["events"][0];
        $result = query($base_select_query."id = ?", "i", $next_match['idEvent']);
        
        if ($result->num_rows == 0) {
            insert_match($next_match);

            $next_match_day = $next_match['dateEvent'];
            $same_day_matches = get_API("eventsday.php?d=$next_match_day&s=Soccer&l=French Ligue 1")['events'];
            insert_update_match($same_day_matches);
        }
    }
