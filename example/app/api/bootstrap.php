<?php

#id = api
#listen = api
#listen = api/...

function users($data, $args)
{
	echo '{"users": ["user1", "user2" ]}';
}

function user($data, $args)
{
	echo '{"name": "User ' . $args['id'] . '"}';
}

divWays::listen("/api/users", "api@users");
divWays::listen("/api/user/{id}", "api@user");
divWays::bootstrap("_url", "/api");