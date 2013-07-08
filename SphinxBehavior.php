<?php
/**
 * Behavior for simple usage of Sphinx search engine
 * http://www.sphinxsearch.com
 *
 *
 * Collaborated and now maintained by Cake N Keyboard <https://github.com/CakeNKeyboard/Sphinx-CakePHP>
 * Updates @author CakeNKeyboard <https://github.com/CakeNKeyboard/>
 *
 * Derived from Nabeel Shahzad <https://github.com/nshahzad/Sphinx-CakePHP>
 *
 * @copyright 2008, Vilen Tambovtsev
 * @author  Vilen Tambovtsev
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */


class SphinxBehavior extends ModelBehavior {

    /**
     * Used for runtime configuration of model
     */
    public $runtime = array();
    public $_defaults = array('server' => 'localhost', 'port' => 9312);
    public $total_results = null;

    /**
     * Spinx client object
     *
     * @var SphinxClient
     */
    public $sphinx = null;

    public function setup(Model $model, $config = array()) {
        $settings = array_merge($this->_defaults, (array)$config);
        $this->settings[$model->alias] = $settings;
    }

    /**
     * beforeFind Callback
     *
     * @param array $query
     * @return array Modified query
     * @access public
     */
    public function beforeFind(Model $model, $query) {

        if (empty($query['sphinx']) || empty($query['search'])) {
			return true;
		}

        if ($model->findQueryType == 'count') {

            $model->recursive = -1;
            $query['limit'] = 1;
            $query['page'] = 1;
			unset($query['conditions']);
			unset($query['order']);
			unset($query['offset']);

			# We already ran the query, and stored the total number, prevent a duplicate query
			# from running, just for the count - NS
			if(!$this->total_results !== null) {
				$this->total_results = !empty($this->total_results) ? $this->total_results : 0;
				$query['fields'] = 'ABS(' . $this->total_results . ') AS count';
				return $query;
			}

        } else if (empty($query['limit'])) {
            $query['limit'] = 9999999;
            $query['page'] = 1;
        }

		App::import('Vendor', 'sphinxapi/sphinxapi');
		$this->runtime[$model->alias]['sphinx'] = new SphinxClient();
		$this->runtime[$model->alias]['sphinx']->setServer($this->settings[$model->alias]['server'], $this->settings[$model->alias]['port']);

        foreach ($query['sphinx'] as $key => $setting) {

            switch ($key) {
                case 'filter':
					if(empty($setting)) {
						break;
					}

                    foreach ($setting as $arg) {
                        $arg[2] = empty($arg[2]) ? false : $arg[2];
                        $this->runtime[$model->alias]['sphinx']->SetFilter($arg[0], (array)$arg[1], $arg[2]);
                    }
                   break;
                case 'filterRange':
                case 'filterFloatRange':
                    $method = 'Set' . $key;
                    foreach ($setting as $arg) {
                        $arg[3] = empty($arg[3]) ? false : $arg[3];
                        $this->runtime[$model->alias]['sphinx']->{$method}($arg[0], (array)$arg[1], $arg[2], $arg[3]);
                    }
                   break;
                case 'matchMode':
                   $this->runtime[$model->alias]['sphinx']->SetMatchMode($setting);
                   break;
                case 'sortMode':
                    $this->runtime[$model->alias]['sphinx']->SetSortMode(key($setting), reset($setting));
                    break;
                default:
                    break;
            }
        }

        $this->runtime[$model->alias]['sphinx']->setLimits(($query['page'] - 1) * $query['limit'], $query['limit']);
        $indexes = !empty($query['sphinx']['index']) ? implode(',' , $query['sphinx']['index']) : '*';
        $result = $this->runtime[$model->alias]['sphinx']->query($query['search'], $indexes);
		$this->total_results = !empty($result['total']) ? $result['total'] : 0;

        if ($result === false) {
            trigger_error("Search query failed: " . $this->runtime[$model->alias]['sphinx']->GetLastError());
            return false;
        } else if(isset($result['matches'])) {
            if ($this->runtime[$model->alias]['sphinx']->GetLastWarning()) {
                trigger_error("Search query warning: " . $this->runtime[$model->alias]['sphinx']->GetLastWarning());
            }
        }

        unset($query['conditions']);
        unset($query['order']);
        unset($query['offset']);

        $query['page'] = 1;

        if ($model->findQueryType == 'count') {
            $result['total'] = !empty($this->total_results) ? $this->total_results : 0;
            $query['fields'] = 'ABS(' . $this->total_results . ') AS count';
        } else {

			$model->sphinx_result = $result; # save to model for use - NS

            if (isset($result['matches'])) {
				$ids = array_keys($result['matches']);
			} else {
				$ids = array(0);
			}

            $query['conditions'] = array($model->alias . '.'.$model->primaryKey => $ids);
            $query['order'] = 'FIND_IN_SET('.$model->alias.'.'.$model->primaryKey.', \'' . implode(',', $ids) . '\')';

        }

        return $query;
    }
}
