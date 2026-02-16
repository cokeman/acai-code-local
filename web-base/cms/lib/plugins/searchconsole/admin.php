<?php

if (isset($_REQUEST['pepe'])) {
    switch ($_REQUEST['pepe']) {
        case 'fetch':
            $response =  API::request('searchconsole', [
                'action' => 'fetch',
            ]);

            die(json_encode($response));

            break;
        case 'inspect':
            $apartados = CocoDB::get('apartados', 'visible_en_el_menu = 1');
            $urls = array_filter(array_column($apartados, 'enlace'));

            $response = API::request('searchconsole', [
                'action' => 'inspect',
                'urls' => $urls,
            ]);

            die(json_encode($response));

            break;
    }

    exit;
}
