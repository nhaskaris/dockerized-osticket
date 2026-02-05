<?php
/*********************************************************************
    http.php

    HTTP controller for the osTicket API

    Jared Hancock
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require 'api.inc.php';
# Include the main api urls
require_once INCLUDE_DIR."class.dispatcher.php";
$dispatcher = patterns('',
        url_post("^/tickets\.(?P<format>xml|json|email)$", array('tickets.php:TicketApiController','create')),
        url_get("^/tickets/list\.(?P<format>xml|json)$", array('tickets.php:TicketReplyApiController','getList')),
        url_post("^/tickets/(?P<id>\d+)/reply\.(?P<format>xml|json)$", array('tickets.php:TicketReplyApiController','reply')),
        url_get("^/tickets/(?P<id>\d+)\.(?P<format>xml|json)$", array('tickets.php:TicketReplyApiController','get')),
        url('^/tasks/', patterns('',
                url_post("^cron$", array('api.cron.php:CronApiController', 'execute'))
         ))
        );

// Send api signal so backend can register endpoints
Signal::send('api', $dispatcher);
# Call the respective function
print $dispatcher->resolve(Osticket::get_path_info());
?>
