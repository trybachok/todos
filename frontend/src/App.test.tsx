import { screen, within } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { ApiError } from './api/notesApi';
import { FakeNotesApi } from './test/FakeNotesApi';
import { renderApp } from './test/renderApp';

const titles = (count: number) =>
  Array.from({ length: count }, (_, index) => `Заметка ${index + 1}`);

async function listedTitles() {
  const list = await screen.findByRole('list', { name: 'Заметки' });
  return within(list)
    .getAllByRole('button')
    .map((item) => item.textContent);
}

describe('Notes app', () => {
  it('lists notes newest first', async () => {
    renderApp(new FakeNotesApi(['Первая', 'Вторая']));

    expect(await listedTitles()).toEqual(['Вторая', 'Первая']);
  });

  it('tells that there are no notes yet', async () => {
    renderApp(new FakeNotesApi());

    expect(await screen.findByText('Заметок пока нет')).toBeVisible();
  });

  it('loads more notes on demand', async () => {
    const user = renderApp(new FakeNotesApi(titles(25)));
    expect(await listedTitles()).toHaveLength(20);

    await user.click(screen.getByRole('button', { name: 'Показать ещё' }));

    expect(
      await screen.findByRole('button', { name: 'Заметка 1' }),
    ).toBeVisible();
    expect(await listedTitles()).toHaveLength(25);
    expect(screen.queryByRole('button', { name: 'Показать ещё' })).toBeNull();
  });

  it('opens a note from the list', async () => {
    const user = renderApp(new FakeNotesApi(['Первая', 'Вторая']));

    await user.click(await screen.findByRole('button', { name: 'Первая' }));

    expect(
      await screen.findByRole('heading', { name: 'Первая' }),
    ).toBeVisible();
    expect(screen.getByText('Текст: Первая')).toBeVisible();
  });

  it('creates a note and opens it', async () => {
    const user = renderApp(new FakeNotesApi(['Первая']));
    await user.click(
      await screen.findByRole('button', { name: 'Новая заметка' }),
    );

    await user.type(screen.getByLabelText('Заголовок'), 'Покупки');
    await user.type(screen.getByLabelText('Текст'), 'молоко');
    await user.click(screen.getByRole('button', { name: 'Сохранить' }));

    expect(
      await screen.findByRole('heading', { name: 'Покупки' }),
    ).toBeVisible();
    expect(screen.getByText('молоко')).toBeVisible();
    expect(await listedTitles()).toEqual(['Покупки', 'Первая']);
  });

  it('edits a note', async () => {
    const user = renderApp(new FakeNotesApi(['Черновик']));
    await user.click(await screen.findByRole('button', { name: 'Черновик' }));
    await user.click(await screen.findByRole('button', { name: 'Изменить' }));

    const title = screen.getByLabelText('Заголовок');
    expect(title).toHaveValue('Черновик');
    await user.clear(title);
    await user.type(title, 'Итог');
    await user.click(screen.getByRole('button', { name: 'Сохранить' }));

    expect(await screen.findByRole('heading', { name: 'Итог' })).toBeVisible();
    expect(await listedTitles()).toEqual(['Итог']);
  });

  it('deletes a note only after confirmation', async () => {
    const user = renderApp(new FakeNotesApi(['Первая', 'Лишняя']));
    await user.click(await screen.findByRole('button', { name: 'Лишняя' }));

    await user.click(await screen.findByRole('button', { name: 'Удалить' }));
    expect(await listedTitles()).toEqual(['Лишняя', 'Первая']);

    await user.click(screen.getByRole('button', { name: 'Да, удалить' }));

    expect(await screen.findByText('Заметка удалена')).toBeVisible();
    expect(await listedTitles()).toEqual(['Первая']);
  });

  it('shows field errors returned by the server', async () => {
    const api = new FakeNotesApi();
    api.create = vi.fn().mockRejectedValue(
      new ApiError({
        type: 'about:blank',
        title: 'Unprocessable Content',
        status: 422,
        detail: 'Проверьте поля заметки',
        errors: { title: 'Введите заголовок' },
      }),
    );
    const user = renderApp(api);
    await user.click(
      await screen.findByRole('button', { name: 'Новая заметка' }),
    );

    await user.click(screen.getByRole('button', { name: 'Сохранить' }));

    const title = screen.getByLabelText('Заголовок');
    expect(title).toBeInvalid();
    expect(title).toHaveAccessibleDescription('Введите заголовок');
  });

  it('reports that the server is unreachable', async () => {
    const api = new FakeNotesApi();
    api.list = vi.fn().mockRejectedValue(new TypeError('Failed to fetch'));

    renderApp(api);

    expect(await screen.findByRole('alert')).toHaveTextContent(
      'Не удалось связаться с сервером',
    );
  });
});
