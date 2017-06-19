<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\CoreHome\Columns;

use Piwik\Piwik;
use Piwik\Plugin\Dimension\VisitDimension;
use Piwik\Tracker\Action;
use Piwik\Tracker\Request;
use Piwik\Tracker\Visitor;

class VisitorReturning extends VisitDimension
{
    const IS_RETURNING_CUSTOMER = 2;
    const IS_RETURNING = 1;
    const IS_NEW = 0;

    protected $columnName = 'visitor_returning';
    protected $columnType = 'TINYINT(1) NULL';
    protected $segmentName = 'visitorType';
    protected $nameSingular = 'General_VisitType';
    protected $conversionField = true;
    protected $category = 'General_Visit';
    protected $type = self::TYPE_ENUM;

    public function __construct()
    {
        $this->acceptValues  = 'new, returning, returningCustomer. ';
        $this->acceptValues .= Piwik::translate('General_VisitTypeExample', '"&segment=visitorType==returning,visitorType==returningCustomer"');

        $this->sqlFilterValue = function ($type) {
            return $type == "new" ? 0 : ($type == "returning" ? 1 : 2);
        };
    }

    /**
     * @param Request $request
     * @param Visitor $visitor
     * @param Action|null $action
     * @return mixed
     */
    public function onNewVisit(Request $request, Visitor $visitor, $action)
    {
        $daysSinceLastOrder = $request->getDaysSinceLastOrder();
        $isReturningCustomer = ($daysSinceLastOrder !== false);

        if ($isReturningCustomer) {
            return self::IS_RETURNING_CUSTOMER;
        }

        $visitCount = $request->getVisitCount();
        $daysSinceFirstVisit = $request->getDaysSinceFirstVisit();
        $daysSinceLastVisit = $request->getDaysSinceLastVisit();

        if ($visitCount > 1 || $visitor->isVisitorKnown() || $daysSinceFirstVisit > 0 || $daysSinceLastVisit > 0) {
            return self::IS_RETURNING;
        }

        return self::IS_NEW;
    }

    /**
     * @param Request $request
     * @param Visitor $visitor
     * @param Action|null $action
     * @return mixed
     */
    public function onAnyGoalConversion(Request $request, Visitor $visitor, $action)
    {
        return $visitor->getVisitorColumn($this->columnName);
    }
}