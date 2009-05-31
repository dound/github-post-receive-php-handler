<?php
/**
 * github POST processor.
 *
 * @author David Underhill
 * @version 0.1 (updated 31-May-2009 @ 06:01 PDT)
 */

/**
 * Emails information about a push specified by github's JSON format.
 *
 * @param to           email address(es)
 * @param subj_header  text to prefix the header with
 * @param github_json  string which contains github's JSON post-receive data
 */
function mail_github_post_receive($to, $subj_header, $github_json) {
    $obj = json_decode($github_json);
    if(!$obj) {
        error_log("bad JSON: $github_json");
        exit(0);
    }

    $num_commits = count($obj->{'commits'});
    if($num_commits == 0) {
        error_log("no commits in JSON: $github_json");
        exit(0);
    }

    // create the subject line
    $branch = str_replace('refs/heads/', '', $obj->{'ref'});
    $last_commit = $obj->{'after'};
    $subj = "$subj_header $branch -> $last_commit";

    // extract information about each commit
    $commits = '';
    $added = array();
    $deleted = array();
    $modified = array();
    foreach($obj->{'commits'} as $commit) {
        $id = $commit->{'id'};
        $url = $commit->{'url'};
        $author = $commit->{'author'};
        $author_name = $author->{'name'};
        $author_email = $author->{'email'};
        $msg = $commit->{'message'};
        $date = $commit->{'timestamp'};

        if(isset($commit->{'added'})) {
            $added = array_merge($added, $commit->{'added'});
        }
        if(isset($commit->{'deleted'})) {
            $deleted = array_merge($deleted, $commit->{'deleted'});
        }
        if(isset($commit->{'modified'})) {
            $modified = array_merge($modified, $commit->{'modified'});
        }

        $commits .= <<<CI
Commit: <a href="$url">$id</a>
Author: $author_name (<a href="mailto:$author_email">$author_email</a>)
Date: $date
<blockquote>$msg</blockquote>

CI;
    }

    // create a list of aggregate additions/deletions/modifications
    $changes = array("Additions"=>$added, "Deletions"=>$deleted, "Modifications"=>$modified);
    $changes_txt = '';
    foreach($changes as $what => $what_list) {
        if(count($what_list) > 0) {
            $changes_txt .= "$what:\n";
            $items = array_unique($what_list);
            sort($items);
            foreach($items as $item) {
                $changes_txt .= " -- $item\n";
            }
        }
    }

    // create the body of the mail
    $repo = $obj->{'repository'};
    $name = $repo->{'name'};
    $url = $repo->{'url'};
    $commits_noun = ($num_commits == 1) ? 'commit' : 'commits';
    $body = <<<BODY
This automated email contains information about $num_commits new $commits_noun which have been
pushed to the '$name' repo located at $url.

$commits
$changes_txt
BODY;

    // send the mail
    if(!mail($to, $subj, $body))
        error_log("failed to email github info to '$to' ($subj, $body)");
    else {
        $body = str_replace("\n", '<br/>', $body);
        echo "$to<br/>$subj<br/>$body<br/>";
    }
}

?>
