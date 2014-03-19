<?php

namespace CanalTP\MttBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * StopPointRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class StopPointRepository extends EntityRepository
{
    public function getBlocks($stopPoint, $timetable)
    {
        $result = $this->getEntityManager()->getRepository('CanalTPMttBundle:Block')->findBy(
            array(
                'stopPoint' => $stopPoint->getId(),
                'timetable' => $timetable->getId()
            )
        );
        return $result;
    }
    
    public function updatePdfGenerationDate($externalStopPointId, $timetable)
    {
        $stopPoint = $this->getStopPoint($externalStopPointId, $timetable);

        $stopPoint->setPdfGenerationDate(new \DateTime());
        $this->getEntityManager()->persist($stopPoint);
        $this->getEntityManager()->flush();
    }

    public function getStopPoint($externalStopPointId, $timetable)
    {
        $stopPoint = $this->findOneBy(array(
            'externalId' => $externalStopPointId,
            'timetable' => $timetable->getId(),

        ));

        // do this stop_point exists?
        if (empty($stopPoint)) {
            $stopPoint = $this->insertStopPoint(
                $externalStopPointId,
                $timetable
            );
        }

        return $stopPoint;
    }

    private function insertStopPoint($externalStopPointId, $timetable)
    {
        $stopPoint = new StopPoint();
        $stopPoint->setExternalId($externalStopPointId);
        $stopPoint->setTimetable($timetable);
        $this->getEntityManager()->persist($stopPoint);

        return $stopPoint;
    }

    private function getLastUpdate($timetable)
    {
        $lastUpdate = null;
        foreach ($timetable->getBlocks() as $block) {
            if ($block->getUpdated() != null && $block->getUpdated() > $lastUpdate) {
                $lastUpdate = $block->getUpdated();
            }
        }

        return $lastUpdate;
    }

    public function hasPdfUpToDate($stopPoint, $timetable)
    {
            // no stop point
        if (empty($stopPoint) ||
            // no pdf generated yet => return FALSE
            $stopPoint->getPdfGenerationDate() == null ||
            // line was modified after pdf generation
            $this->getLastUpdate($timetable) > $stopPoint->getPdfGenerationDate()) {
            return false;
        } else {
            return true;
        }
    }
}
