<?php

declare(strict_types=1);

namespace App\Notes\Http;

use App\Notes\Domain\NoteRepository;
use App\Notes\Domain\NoteValidator;
use App\Notes\Domain\ValidationErrors;
use App\Notes\Infrastructure\BitrixNoteRepository;
use Bitrix\Main\Application;
use Bitrix\Main\Diag\ExceptionHandlerLog;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\HttpResponse;
use Throwable;

final class NoteController extends Controller
{
    private NoteRepository $notes;
    private NoteValidator $validator;

    protected function init(): void
    {
        parent::init();
        $this->notes = new BitrixNoteRepository();
        $this->validator = new NoteValidator();
    }

    /**
     * No authentication and no CSRF check: the API is public and stateless, and the HTTP methods are bound by the routes.
     *
     * @return array{}
     */
    protected function getDefaultPreFilters(): array
    {
        return [];
    }

    /**
     * Otherwise Bitrix turns an exception into a response with its message.
     */
    protected function getActionResponse(Action $action): mixed
    {
        try {
            return parent::getActionResponse($action);
        } catch (Throwable $e) {
            Application::getInstance()->getExceptionHandler()->writeToLog($e, ExceptionHandlerLog::CAUGHT_EXCEPTION);

            return Problem::internalError();
        }
    }

    public function listAction(): HttpResponse
    {
        $page = $this->validator->validatePage($this->getRequest()->getQueryList()->toArray());

        return $page instanceof ValidationErrors
            ? Problem::invalid($page)
            : JsonResponse::create(NotePresenter::list($this->notes->list($page)));
    }
}
