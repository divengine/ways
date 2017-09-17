<?php

#id = api
#listen = api
#listen = api/...

function users($data)
{
	echo '{"users": ["user1", "user2" ]}';
}

divWays::listen("/api/users", "api@users");

divWays::bootstrap("_url", "/api");