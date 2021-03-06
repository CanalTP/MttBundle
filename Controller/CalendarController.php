<?php

namespace CanalTP\MttBundle\Controller;

/*
 * CalendarController
 */

use CanalTP\MttBundle\Calendar\CalendarExport;
use CanalTP\MttBundle\Entity\Calendar;
use CanalTP\MttBundle\Form\Type\CalendarType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Guzzle\Http\Message\MessageInterface as GuzzleHttpResponse;

class CalendarController extends AbstractController
{

    public function createAction(Request $request)
    {
        $em = $this->get('doctrine')->getManager();
        $translator = $this->get('translator');

        $calendar = new Calendar();

        $form = $this->createForm(new CalendarType(), $calendar);

        $form->handleRequest($request);

        if ($request->isMethod('POST') && $form->isValid()) {
            $calendar->setCustomer($this->getUser()->getCustomer());
            $em->persist($calendar);
            $em->flush();
            $this->addFlash('success', $translator->trans('calendar.create.success', [], 'default'));

            return $this->redirectToRoute('canal_tp_mtt_calendars_create');
        }

        return $this->render('CanalTPMttBundle:Calendar:create.html.twig', ['form' => $form->createView()]);
    }

    public function viewAction($externalNetworkId, $externalRouteId, $externalStopPointId, $currentSeasonId)
    {
        $calendarManager = $this->get('canal_tp_mtt.calendar_manager');
        $perimeterManager = $this->get('nmm.perimeter_manager');
        $stopPointManager = $this->get('canal_tp_mtt.stop_point_manager');

        $perimeter = $perimeterManager->findOneByExternalNetworkId(
            $this->getUser()->getCustomer(),
            $externalNetworkId
        );
        $calendars = $calendarManager->getCalendarsForStopPoint(
            $perimeter->getExternalCoverageId(),
            $externalRouteId,
            $externalStopPointId
        );

        $prevNextStopPoints = $stopPointManager->getPrevNextStopPoints(
            $perimeter,
            $externalRouteId,
            $externalStopPointId
        );

        $currentSeason = $this->get('canal_tp_mtt.season_manager')->find($currentSeasonId);

        return $this->render(
            'CanalTPMttBundle:Calendar:view.html.twig',
            array(
                'pageTitle'           => $this->get('translator')->trans(
                    'calendar.view_title',
                    array(),
                    'default'
                ),
                'externalNetworkId'   => $externalNetworkId,
                'externalStopPointId' => $externalStopPointId,
                'calendars'           => $calendars,
                'current_route'       => $externalRouteId,
                'currentSeason'       => $currentSeason,
                'prevNextStopPoints'  => $prevNextStopPoints,
            )
        );
    }

    /**
     * Displays calendar list
     *
     * @return type
     */
    public function listAction()
    {
        $calendars = $this->getDoctrine()
            ->getRepository('CanalTPMttBundle:Calendar')
            ->findBy(
                ['customer' => $this->getUser()->getCustomer()],
                ['id'=>'desc']
            );

        return $this->render('CanalTPMttBundle:Calendar:list.html.twig', [
          'no_left_menu' => true,
          'calendars'    => $calendars
        ]);
    }

    /**
     * Export a zip with calendars
     *
     * @return Response
     */
    public function exportAction()
    {
        $applicationCanonicalName = $this->get('canal_tp_sam.application.finder')->getCurrentApp()->getCanonicalName();
        $externalCoverageId = $this->getUser()->getCustomer()->getPerimeters()->first()->getExternalCoverageId();
        $calendars = $this->getDoctrine()->getRepository('CanalTPMttBundle:Calendar')->findCalendarByExternalCoverageIdAndApplication($externalCoverageId, $applicationCanonicalName);

        try {
            $calendarExport = $this->get('canal_tp_mtt.calendar_export');
            $response = $calendarExport->export($externalCoverageId, $calendars);

            if (!$response->isSuccessful()) {
                throw new \RuntimeException($this->getMessageFromResponse($response));
            }

            $flashType  = 'success';
            $message = $this->getMessageFromResponse($response);
        } catch (\Exception $ex) {
            $flashType  = 'danger';
            $message = $ex->getMessage();
        }

        $this->addFlash($flashType, $message);

        return $this->redirectToRoute('canal_tp_mtt_calendars_list');
    }

    /**
     * Retrieves message from json body
     *
     * @param GuzzleHttpResponse $response
     *
     * @return string
     */
    private function getMessageFromResponse(GuzzleHttpResponse $response)
    {
        $jsonBody = $response->json(array('object' => true));

        return $jsonBody['message'];
    }

    public function editAction(Request $request, $calendarId)
    {
        $em = $this->get('doctrine')->getManager();
        $translator = $this->get('translator');

        $calendar = $this->getDoctrine()
            ->getRepository('CanalTPMttBundle:Calendar')
            ->findOneBy(['customer' => $this->getUser()->getCustomer(), 'id' => $calendarId]);

        if (null === $calendar) {
            throw $this->createNotFoundException(
                $translator->trans(
                    'services.calendar_manager.calendar_not_found',
                    array('%calendarId%' => $calendarId),
                    'exceptions'
                )
            );
        }

        $form = $this->createForm(new CalendarType(), $calendar);
        $form->handleRequest($request);

        if ($request->isMethod('POST') && $form->isValid()) {
            $em->persist($calendar);
            $em->flush();
            $this->addFlash('success', $translator->trans('calendar.edit.success', [], 'default'));
    
            return $this->redirectToRoute('canal_tp_mtt_calendars_list');
        }

        return $this->render('CanalTPMttBundle:Calendar:edit.html.twig', ['form' => $form->createView()]);
    }

    public function deleteAction(Request $request, $calendarId = null)
    {
        $calendar = $this->getDoctrine()
            ->getRepository('CanalTPMttBundle:Calendar')
            ->findOneBy(['customer' => $this->getUser()->getCustomer(), 'id' => $calendarId]);

        if (null === $calendar) {
            throw $this->createNotFoundException(
                $this->get('translator')->trans(
                    'services.calendar_manager.calendar_not_found',
                    array('%calendarId%' => $calendarId),
                    'exceptions'
                )
            );
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($calendar);
        $em->flush();
        
        $this->get('session')->getFlashBag()->add(
            'success',
            $this->get('translator')->trans(
                'calendar.deleted',
                array(),
                'default'
            )
        );
        
        return $this->redirectToRoute('canal_tp_mtt_calendars_list');
    }
}
