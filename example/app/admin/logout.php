<?php

/* Div PHP Control example */

#id = admin-logout
#listen = admin/logout

unset($_SESSION['login']);

header("Location: /admin");
exit();
