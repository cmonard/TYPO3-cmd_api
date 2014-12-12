<?php

namespace CMD\CmdApi;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christophe Monard <contact@cmonard.fr>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

/**
 * Librairie de fonction dédié à la manipulation du Scheduler
 *
 * @author	Christophe Monard   <contact@cmonard.fr>
 *
 * Call method:
 * 	$cmdScheduler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('CMD\\CmdApi\\Scheduler');
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *   59: class Scheduler
 *   73:        public function __construct($onlyClassname = '', $includeDisabled = TRUE)
 *  104:        public function getTask($id = 0)
 *  121:        public function isValidTask($id)
 *  135:        public function isTaskPlanned($id)
 *  149:        public function isTaskRunning($id)
 *  163:        public function destroyTask($id)
 *  188:        public function activateTask($idOrClassname, $parameters = array())
 *
 * TOTAL FUNCTIONS: 7
 *
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Scheduler class of the extension
 *
 * @author	Christophe Monard <contact@cmonard.fr>
 */
class Scheduler {

        protected $scheduler = object;
        protected $task = array();
        public $taskCount = 0;

        /**
         * Class constructor
         * Retrieve task from scheduler, can be limited by using 2 parameters
         *
         * @param	array		$onlyClassname: only class like these are fetched
         * @param	bool		$includeDisabled: disabled task will be include during fetching
         * @return	void            FALSE if Scheduler isn't loaded
         */
        public function __construct($onlyClassname = array(), $includeDisabled = TRUE) {
                if (ExtensionManagementUtility::isLoaded('scheduler')) {
                        $this->scheduler = GeneralUtility::makeInstance('TYPO3\\CMS\\Scheduler\\Scheduler');
                        $params = array(
                            'classname' => $onlyClassname,
                            'includeDisabled' => $includeDisabled,
                        );
                        Api::getHook('scheduler_constructor_where', $params, $this);
                        $taskRecords = $this->scheduler->fetchTasksWithCondition('', $includeDisabled);
                        if (count($taskRecords) > 0) {
                                $onlyClassname = (array) $onlyClassname; // make sure we have an array
                                foreach ($taskRecords as $task) {
                                        if (empty($onlyClassname) || GeneralUtility::inArray($onlyClassname, $task->getTaskClassName())) {
                                                $this->task[intval($task->getTaskUid())] = $task;
                                        }
                                }
                                $this->taskCount = count($this->task);
                        }
                        Api::getHook('scheduler_constructor', $params, $this);
                } else {
                        return FALSE;
                }
        }

        /**
         * Function returning array of task found during initialisation or returning specific task by providing is ID
         * Return FALSE if id is provided but not found in task array
         *
         * @param	int		$id: id of the task to get
         * @return	mixed           FALSE if task id not found, task or array of tasks otherwise
         */
        public function getTask($id = 0) {
                if ($id > 0 && in_array($id, array_keys($this->task))) {
                        return $this->task[$id];
                } elseif ($id == 0) {
                        return $this->task;
                } else {
                        return FALSE;
                }
        }

        /**
         * Function returning if selected task id is a valid task from scheduler
         * Return FALSE if id is provided but not found in task array of task isn't a valid task object
         *
         * @param	int		$id: id of the task to get
         * @return	mixed           FALSE if task id not found or not a valid task object, TRUE otherwise
         */
        public function isValidTask($id) {
                if (isset($this->task[$id]) && $this->scheduler->isValidTaskObject($this->task[$id])) {
                        return TRUE;
                } else {
                        return FALSE;
                }
        }

        /**
         * Function returning if the task is planned for execution or not
         *
         * @param	int		$id: id of the task to test
         * @return	bool
         */
        public function isTaskPlanned($id) {
                if (isset($this->task[$id]) && $this->scheduler->isValidTaskObject($this->task[$id]) && !$this->task[$id]->isDisabled()) {
                        return TRUE;
                } else {
                        return FALSE;
                }
        }

        /**
         * Function returning if the task is running or not
         *
         * @param	int		$id: id of the task to test
         * @return	bool
         */
        public function isTaskRunning($id) {
                if (isset($this->task[$id]) && $this->scheduler->isValidTaskObject($this->task[$id]) && $this->task[$id]->isExecutionRunning()) {
                        return TRUE;
                } else {
                        return FALSE;
                }
        }

        /**
         * Function removing a task from the scheduler
         *
         * @param	int		$id: id of the task to remove
         * @return	bool            TRUE or FALSE depending if remove was successful (task found, not running, etc.)
         */
        public function destroyTask($id) {
                if (isset($this->task[$id]) && $this->scheduler->isValidTaskObject($this->task[$id]) && !$this->isTaskRunning($id)) {
                        return $this->scheduler->removeTask($this->task[$id]);
                } else {
                        return FALSE;
                }
        }

        /**
         * Function creating or updating a task, and scheduling it
         *
         * @param	mixed		$idOrClassname: id of the task to update or classname of the task to create
         * @param	array		$parameters: array contening parameters of the task
         *                                              // starttime: starttime to use for the task, EXEC_TIME if not provided
         *                                              // recurring: array defining a recurring task, if not provided, task will be single run
         *                                              // recurring|interval: number of seconds between task execution, 0 if not provided
         *                                              // recurring|endtime: entime of the task, 0 if not provided
         *                                              // recurring|multiple: can it be run in multiple instance paralellized, TRUE or FALSE
         *                                              // recurring|cron: cron syntaxe to use if interval is not provided
         *                                              // options: array of method to use for defining options of the task
         *                                                              eg. setRootpage => 2 will execute the method setRootpage using 2 as the first parameters
         *                                                              eg. setMail => array('my name', 'email@company.com') will execute the method setMail using 'my name' as the first parameters
         *                                                                      and 'email@company.com' as second
         * @return	bool            TRUE or FALSE depending if remove was successful (task found, not running, etc.)
         */
        public function activateTask($idOrClassname, $parameters = array()) {
                $addTask = FALSE;
                // is an existing task or a new one?
                if (MathUtility::canBeInterpretedAsInteger($idOrClassname) && $this->scheduler->isValidTaskObject($this->task[$idOrClassname])) { // We provide id, we want a run
                        $task = &$this->task[$idOrClassname];
                } elseif (!MathUtility::canBeInterpretedAsInteger($idOrClassname)) { // We povide classname, we want new task
                        $task = GeneralUtility::makeInstance($idOrClassname);
                        $addTask = TRUE;
                }
                // if we have a valid task we can work on it
                if ($this->scheduler->isValidTaskObject($task)) {
                        // we call hook for external manipulation
                        $hookConf = array(
                            'idOrClassname' => $idOrClassname,
                            'taskParameters' => $parameters,
                            'isNewTask' => $addTask,
                            'task' => &$task,
                        );
                        Api::getHook('scheduler_activateTask_begin', $hookConf, $this);
                        // task parameters
                        $taskStarttime = isset($parameters['starttime']) && MathUtility::canBeInterpretedAsInteger($parameters['starttime']) ? $parameters['starttime'] : $GLOBALS['EXEC_TIME'];
                        if (isset($parameters['recurring'])) {
                                $r = &$parameters['recurring'];
                                $taskInterval = isset($r['interval']) && MathUtility::canBeInterpretedAsInteger($r['interval']) ? $r['interval'] : 0;
                                $taskEndtime = isset($r['endtime']) && MathUtility::canBeInterpretedAsInteger($r['endtime']) ? $r['endtime'] : 0;
                                $taskParallel = isset($r['multiple']) && $r['multiple'] ? TRUE : FALSE;
                                $taskCronline = isset($r['cron']) ? $r['cron'] : '';
                                if ($taskInterval == 0 && $taskCronline == '') {
                                        $taskInterval = 60;
                                }
                                $task->registerRecurringExecution($taskStarttime, $taskInterval, $taskEndtime, $taskParallel, $taskCronline);
                        } else {
                                $task->registerSingleExecution($taskStarttime);
                        }
                        $task->setDisabled(FALSE);
                        if (is_array($parameters['options'])) {
                                foreach ($parameters['options'] as $option => $value) {
                                        if (method_exists($task, $option)) {
                                                call_user_func_array(array($task, $option), (array) $value);
                                        }
                                }
                        }
                        if ($addTask) {
                                $this->scheduler->addTask($task);
                                $this->task[intval($task->getTaskUid())] = $task;
                        } else {
                                $task->save();
                        }
                        Api::getHook('scheduler_activateTask_end', $hookConf, $this);
                }
        }

}
