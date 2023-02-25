<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Util;

/**
 * Class used to display the Search Table Modal
 */
final class SearchTablesModalController extends AbstractController
{
    public function __invoke(ServerRequest $request): void
    {
        global $db, $table, $url;

        $db= $_POST['db'];
        $table= $_POST['table'];
        // If user has already selected a table, the page they are currently on will remain same upon selecting a different table.
        if($table){
            $url= end(explode('=', explode('&',$_POST['current_url'])[0]));
        }
        [$tables] = Util::getDbInfo($request, $db, true);
        $data= $this->template->render('modals/search_tables', [
            'tables' => array_keys($tables), 'db'=> $db, 'url_query'=>$url]);

        $this->response->addJSON(['data'=> html_entity_decode($data), 'db'=> $db]);
    }
}