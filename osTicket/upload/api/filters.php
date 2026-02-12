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

    /**
     * Update an existing filter
     * PUT /api/filters/{id}.json
     */
    function update($id) {
        // 1. Start a safety net for Fatal Errors
        try {
            // DEBUG: Check if we even get here
            if (!$id) throw new Exception("ID is missing or zero.");

            // 2. Auth Check
            $key = $this->requireApiKey();
            if (!$key) throw new Exception("API Key Missing or Invalid");

            // 3. Look up the Filter
            $filter = Filter::lookup($id);
            if (!$filter) throw new Exception("Filter #$id not found in database");

            // 4. Get the raw PUT data
            $input = file_get_contents('php://input');
            if (!$input) throw new Exception("No JSON body received");
            
            $data = json_decode($input, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON Format: " . json_last_error_msg());
            }

            // 5. Update Basic Info
            if (isset($data['name'])) $filter->name = $data['name'];
            if (isset($data['execorder'])) $filter->execorder = $data['execorder'];
            if (isset($data['isactive'])) $filter->isactive = (bool)$data['isactive'];
            if (isset($data['match_all_rules'])) $filter->match_all_rules = (bool)$data['match_all_rules'];
            if (isset($data['stop_onmatch'])) $filter->stop_onmatch = (bool)$data['stop_onmatch'];
            if (isset($data['target'])) $filter->target = $data['target'];
            if (isset($data['notes'])) $filter->notes = $data['notes'];
            
            $filter->updated = date('Y-m-d H:i:s');

            if (!$filter->save()) {
                throw new Exception("Database refused to save Filter update");
            }

            // 6. Update Rules (Delete Old -> Add New)
            if (isset($data['rules']) && is_array($data['rules'])) {
                // Debug: Check if FilterRule class exists
                if (!class_exists('FilterRule')) throw new Exception("Class FilterRule not found!");
                
                FilterRule::objects()->filter(['filter_id' => $id])->delete();
                
                foreach ($data['rules'] as $idx => $r) {
                    $rule = new FilterRule([
                        'filter_id' => $filter->id,
                        'what'      => $r['what'],
                        'how'       => $r['how'],
                        'val'       => $r['val'],
                        'isactive'  => isset($r['isactive']) ? (bool)$r['isactive'] : true,
                        'created'   => date('Y-m-d H:i:s'),
                        'updated'   => date('Y-m-d H:i:s'),
                    ]);
                    if (!$rule->save()) throw new Exception("Failed to save Rule #$idx");
                }
            }

            // 7. Update Actions (Delete Old -> Add New)
            if (isset($data['actions']) && is_array($data['actions'])) {
                 // Debug: Check if FilterAction class exists
                if (!class_exists('FilterAction')) throw new Exception("Class FilterAction not found!");

                FilterAction::objects()->filter(['filter_id' => $id])->delete();
                
                foreach ($data['actions'] as $idx => $a) {
                    $config = is_array($a['configuration']) 
                              ? json_encode($a['configuration']) 
                              : ($a['configuration'] ?? '{}');

                    $action = new FilterAction([
                        'filter_id'     => $filter->id,
                        'type'          => $a['type'],
                        'configuration' => $config,
                        'updated'       => date('Y-m-d H:i:s'),
                    ]);
                    if (!$action->save()) throw new Exception("Failed to save Action #$idx");
                }
            }

            // 8. Success!
            return $this->response(200, json_encode([
                'status' => 'success',
                'message' => "Filter $id updated successfully",
                'debug_info' => 'Reached end of function'
            ]), 'application/json');

        } catch (Throwable $e) {
            // 9. CATCH THE ERROR AND RETURN IT
            // We use 'Throwable' to catch both Exceptions AND Fatal Errors (PHP 7+)
            return $this->response(500, json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]), 'application/json');
        }
    }

    /**
     * GET /api/filters/fields
     * Returns a list of all possible "what" criteria for rules.
     */
    function getFields() {
        if (!($key = $this->requireApiKey()))
            return $this->exerr(401, 'API key not authorized');

        // FIX: Use the correct method 'getSupportedMatches'
        $matches = Filter::getSupportedMatches();

        $result = [];

        foreach ($matches as $group => $fields) {
            // $fields is usually an array (e.g., 'User Data' => ['email' => 'Email Address'])
            if (is_array($fields)) {
                foreach ($fields as $key => $label) {
                    // This creates labels like "User Information: Email"
                    $result[] = [
                        'key'   => $key,
                        'label' => "$group: $label"
                    ];
                }
            } else {
                // Handle edge cases where it might not be nested
                $result[] = [
                    'key'   => $group,
                    'label' => $fields
                ];
            }
        }

        return $this->response(200, json_encode(['fields' => $result]), 'application/json');
    }
}