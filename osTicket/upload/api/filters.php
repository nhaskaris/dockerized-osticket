<?php
require_once INCLUDE_DIR.'class.filter.php';
require_once INCLUDE_DIR.'class.filter_action.php';
require_once INCLUDE_DIR.'class.api.php';

class FilterApiController extends ApiController {

    private function getResourceId() {
        // Scrape ID from the actual URL path: /api/filters/123
        if (preg_match('/\/filters\/(\d+)/', $_SERVER['REQUEST_URI'], $m)) {
            return $m[1];
        }
        return null;
    }

    function getList($format='json') {
        try {
            if (!($key = $this->requireApiKey()))
                return $this->exerr(401, 'API key not authorized');

            $filters = Filter::objects()->order_by('execorder')->all();
            $result = [];

            foreach ($filters as $f) {
                $rules = [];
                $fRules = FilterRule::objects()->filter(['filter_id'=>$f->id])->all();
                foreach ($fRules as $r) {
                    $rules[] = ['id'=>$r->id, 'what'=>$r->what, 'how'=>$r->how, 'val'=>$r->val];
                }

                $actions = [];
                $fActions = FilterAction::objects()->filter(['filter_id'=>$f->id])->all();
                foreach ($fActions as $a) {
                    $actions[] = ['id'=>$a->id, 'type'=>$a->type, 'configuration'=>$a->configuration];
                }

                $result[] = [
                    'id' => (int)$f->id,
                    'name' => $f->name,
                    'isactive' => (bool)$f->isactive,
                    'target' => $f->target,
                    'notes' => $f->notes,
                    'rules' => $rules,
                    'actions' => $actions
                ];
            }
            return $this->response(200, json_encode(['filters' => $result]), 'application/json');
        } catch (Throwable $t) {
            return $this->response(500, json_encode(['error' => $t->getMessage()]), 'application/json');
        }
    }

    function delete($args) {
        try {
            if (!($key = $this->requireApiKey()))
                return $this->exerr(401, 'API key not authorized');

            $id = $this->getResourceId();
            if (!$id) return $this->exerr(400, 'ID required in URL path');

            $filter = Filter::lookup($id);
            if (!$filter) return $this->exerr(404, "Filter #$id not found");

            FilterRule::objects()->filter(['filter_id' => $id])->delete();
            FilterAction::objects()->filter(['filter_id' => $id])->delete();

            if (!$filter->delete()) return $this->exerr(500, 'Delete failed');

            return $this->response(200, json_encode(['message' => 'Deleted successfully']), 'application/json');
        } catch (Throwable $t) {
            return $this->response(500, json_encode(['error' => $t->getMessage()]), 'application/json');
        }
    }

    function create($format='json') {
        try {
            if (!($key = $this->requireApiKey()))
                return $this->exerr(401, 'API key not authorized');

            $data = $this->getRequest('json');
            if (!$data || !isset($data['name'])) return $this->exerr(400, 'Name required');

            // 1. Save the Main Filter
            $filter = new Filter([
                'name'            => $data['name'],
                'execorder'       => $data['execorder'] ?? 99,
                'isactive'        => isset($data['isactive']) ? (bool)$data['isactive'] : true,
                'match_all_rules' => isset($data['match_all_rules']) ? (bool)$data['match_all_rules'] : false,
                'stop_onmatch'    => isset($data['stop_onmatch']) ? (bool)$data['stop_onmatch'] : false,
                'target'          => $data['target'] ?? 'Any',
                'email_id'        => $data['email_id'] ?? 0,
                'notes'           => $data['notes'] ?? '',
                'created'         => date('Y-m-d H:i:s'),
                'updated'         => date('Y-m-d H:i:s'),
            ]);

            if (!$filter->save()) return $this->exerr(500, 'Filter save failed');

            // 2. Save Rules
            if (isset($data['rules']) && is_array($data['rules'])) {
                foreach ($data['rules'] as $r) {
                    $rule = new FilterRule([
                        'filter_id' => $filter->id,
                        'what'      => $r['what'],
                        'how'       => $r['how'],
                        'val'       => $r['val'],
                        'isactive'  => isset($r['isactive']) ? (bool)$r['isactive'] : true,
                        'created'   => date('Y-m-d H:i:s'),
                        'updated'   => date('Y-m-d H:i:s'),
                    ]);
                    $rule->save();
                }
            }

            // 3. Save Actions
            if (isset($data['actions']) && is_array($data['actions'])) {
                foreach ($data['actions'] as $a) {
                    $action = new FilterAction([
                        'filter_id'     => $filter->id,
                        'type'          => $a['type'],
                        'configuration' => is_array($a['configuration']) 
                                           ? json_encode($a['configuration']) 
                                           : ($a['configuration'] ?? '{}'),
                        'updated'       => date('Y-m-d H:i:s'),
                    ]);
                    $action->save();
                }
            }

            return $this->response(201, json_encode([
                'id' => $filter->id, 
                'message' => 'Filter, Rules, and Actions created successfully'
            ]), 'application/json');

        } catch (Throwable $t) {
            return $this->response(500, json_encode(['error' => $t->getMessage()]), 'application/json');
        }
    }
}