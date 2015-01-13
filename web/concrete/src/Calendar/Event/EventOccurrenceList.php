<?php
namespace Concrete\Core\Calendar\Event;

use Concrete\Core\Search\ItemList\Database\ItemList;
use Concrete\Core\Search\Pagination\Pagination;
use Concrete\Core\Calendar\Calendar;
use Pagerfanta\Adapter\DoctrineDbalAdapter;

class EventOccurrenceList extends ItemList
{

    protected $ev;

    public function getResult($row)
    {
        return EventOccurrence::getByID($row['occurrenceID']);
    }

    public function filterByEvent(Event $ev)
    {
        $this->query->andWhere('eo.eventID = :eventID');
        $this->query->setParameter('eventID', $ev->getID());
    }

    public function filterByCalendar(Calendar $calendar)
    {
        $this->query->andWhere('e.caID = :caID');
        $this->query->setParameter('caID', $calendar->getID());
    }

    public function filterByDate($date)
    {

        $startOfDay = strtotime($date . ' 00:00:00');
        $endOfDay = strtotime($date . ' 23:59:59');

        $this->query->andWhere(
            $this->query->expr()->orX(
                $this->query->expr()->andX(
                    $this->query->expr()->comparison('eo.startTime', '<=', ':startOfDay'),
                    $this->query->expr()->comparison('eo.endTime', '>', ':startOfDay')
                ),
                $this->query->expr()->andX(
                    $this->query->expr()->comparison('eo.startTime', '>=', ':startOfDay'),
                    $this->query->expr()->comparison('eo.startTime', '<=', ':endOfDay')
                ),
                $this->query->expr()->andX(
                    $this->query->expr()->comparison('eo.startTime', '<=', ':startOfDay'),
                    $this->query->expr()->comparison('eo.endTime', '>=', ':endOfDay')
                )
            )
        );
        $this->query->setParameter('startOfDay', $startOfDay);
        $this->query->setParameter('endOfDay', $endOfDay);
    }
    /**
     * Returns the total results in this item list.
     *
     * @return int
     */
    public function getTotalResults()
    {
        $query = $this->deliverQueryObject();
        return $query->select('count(distinct eo.occurrenceID)')->setMaxResults(1)->execute()->fetchColumn();

    }

    public function createQuery()
    {
        $this->query->select('eo.occurrenceID')->from('CalendarEventOccurrences', 'eo')
            ->innerJoin('eo', 'CalendarEvents', 'e', 'e.eventID = eo.eventID');

    }

    /**
     * @return \Concrete\Core\Search\Pagination\Pagination
     */
    protected function createPaginationObject()
    {
        $adapter = new DoctrineDbalAdapter(
            $this->deliverQueryObject(), function ($query) {
            $query->select('count(distinct eo.occurrenceID)')->setMaxResults(1);
        });
        return new Pagination($this, $adapter);
    }
}