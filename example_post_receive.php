<?php

// two ways to address the email
// option 1) manually set the list
$email_to = "dgu@cs.stanford.edu";

// option 2) use the list maintainer so people can sign-up for the list (and
// leave) on their own (recommended)
// note: to setup your own list, go to http://yuba.stanford.edu/github/list_create.php
include 'list_funcs.php';
$email_to = list_get_active_to_addrs_as_string('example');

// how to prefix the subject line (e.g., project name)
$email_subj_prefix = "[ENVI]";

// example input (should really set this by: $json = $_POST['payload'];)
$json = <<<JSON
{
  "before": "5aef35982fb2d34e9d9d4502f6ede1072793222d",
  "repository": {
    "url": "http://github.com/defunkt/github",
    "name": "github",
    "description": "You're lookin' at it.",
    "watchers": 5,
    "forks": 2,
    "private": 1,
    "owner": {
      "email": "chris@ozmm.org",
      "name": "defunkt"
    }
  },
  "commits": [
    {
      "id": "41a212ee83ca127e3c8cf465891ab7216a705f59",
      "url": "http://github.com/defunkt/github/commit/41a212ee83ca127e3c8cf465891ab7216a705f59",
      "author": {
        "email": "chris@ozmm.org",
        "name": "Chris Wanstrath"
      },
      "message": "okay i give in",
      "timestamp": "2008-02-15T14:57:17-08:00",
      "added": ["filepath.rb"]
    },
    {
      "id": "de8251ff97ee194a289832576287d6f8ad74e3d0",
      "url": "http://github.com/defunkt/github/commit/de8251ff97ee194a289832576287d6f8ad74e3d0",
      "author": {
        "email": "chris@ozmm.org",
        "name": "Chris Wanstrath"
      },
      "message": "update pricing a tad",
      "timestamp": "2008-02-15T14:36:34-08:00"
    }
  ],
  "after": "de8251ff97ee194a289832576287d6f8ad74e3d0",
  "ref": "refs/heads/master"
}
JSON;

include 'github_post_receive.php';
mail_github_post_receive($email_to, $email_subj_prefix, $json);

?>
