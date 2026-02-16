<?php
require 'api.inc.php';
require_once INCLUDE_DIR . 'class.list.php';
require_once INCLUDE_DIR . 'class.api.php';

class ListsApiController extends ApiController {
    private function getListIdFromHeader() {
        if (!empty($_SERVER['HTTP_X_LIST_ID']))
            return (int) $_SERVER['HTTP_X_LIST_ID'];
        if (!empty($_SERVER['HTTP_LIST_ID']))
            return (int) $_SERVER['HTTP_LIST_ID'];
        return 0;
    }

    private function lookupList($data, $pathListId=0) {
        if ($pathListId)
            return DynamicList::lookup((int) $pathListId);

        $headerListId = $this->getListIdFromHeader();
        if ($headerListId)
            return DynamicList::lookup($headerListId);

        if (!empty($data['list_id']))
            return DynamicList::lookup((int) $data['list_id']);

        $name = isset($data['list_name']) ? trim($data['list_name']) : 'Projects';
        if ($name == '')
            return null;

        return DynamicList::objects()->filter(array('name' => $name))->first();
    }

    function getList($format='json') {
        try {
            if (!($key = $this->requireApiKey()))
                return $this->exerr(401, 'API key not authorized');

            $lists = DynamicList::objects()->order_by('name');
            $result = array();

            foreach ($lists as $list) {
                $result[] = array(
                    'id' => (int) $list->getId(),
                    'name' => $list->getName(),
                    'name_plural' => $list->getPluralName(),
                    'item_count' => (int) $list->getNumItems()
                );
            }

            return $this->response(200, json_encode(array(
                'lists' => $result
            )), 'application/json');
        } catch (Throwable $t) {
            return $this->response(500, json_encode(array(
                'error' => $t->getMessage()
            )), 'application/json');
        }
    }

    function create($id, $format='json') {
        try {
            if (!($key = $this->requireApiKey()))
                return $this->exerr(401, 'API key not authorized');

            $data = $this->getRequest('json');
            if (!$data)
                return $this->exerr(400, 'JSON body required');

            if (!isset($data['values']) || !is_array($data['values']))
                return $this->exerr(400, 'values array required');

            $list = $this->lookupList($data, $id);
            if (!$list)
                return $this->exerr(404, 'Project list not found');

            $inserted = 0;
            $skipped = 0;
            $errors = array();

            foreach ($data['values'] as $raw) {
                $value = trim((string) $raw);
                if ($value === '') {
                    $skipped++;
                    continue;
                }

                if ($list->getItem($value)) {
                    $skipped++;
                    continue;
                }

                $item = $list->addItem(array(
                    'sort' => 0,
                    'value' => $value,
                    'extra' => ''
                ), $errors);

                if ($item && $item->save()) {
                    $inserted++;
                } else {
                    $skipped++;
                }
            }

            return $this->response(201, json_encode(array(
                'list_id' => (int) $list->getId(),
                'inserted' => $inserted,
                'skipped' => $skipped,
                'errors' => $errors
            )), 'application/json');
        } catch (Throwable $t) {
            return $this->response(500, json_encode(array(
                'error' => $t->getMessage()
            )), 'application/json');
        }
    }
}

?>
