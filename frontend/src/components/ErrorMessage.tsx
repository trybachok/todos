import { ApiError } from '../api/notesApi';

export function ErrorMessage({ error }: { error: unknown }) {
  return (
    <p className="error" role="alert">
      {error instanceof ApiError
        ? (error.problem.detail ?? error.problem.title)
        : 'Не удалось связаться с сервером. Проверьте подключение и повторите попытку.'}
    </p>
  );
}
