import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest';
import { createApp } from 'vue';
import { setActivePinia, createPinia } from 'pinia';
import { VueQueryPlugin } from '@tanstack/vue-query';
import { queryClient } from '@/queryClient';
import { safeFetch } from '@/utils/safeFetch';
import { useAuthStore } from '@/stores/auth';

describe('safeFetch utility', () => {
  let app: ReturnType<typeof createApp>;

  beforeEach(() => {
    const pinia = createPinia();
    app = createApp({});
    app.use(pinia);
    app.use(VueQueryPlugin, { queryClient });
    setActivePinia(pinia);
    vi.restoreAllMocks();
    localStorage.clear();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  test('executes successful HTTP fetch', async () => {
    const mockResponse = new Response(JSON.stringify({ status: 'ok' }), { status: 200 });
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(mockResponse));

    const res = await safeFetch('https://api.example.com/data');
    expect(res.status).toBe(200);
    expect(fetch).toHaveBeenCalledTimes(1);
  });

  test('throws user-friendly error on request timeout / abort', async () => {
    const abortError = new Error('The operation was aborted');
    abortError.name = 'AbortError';

    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(abortError));

    await expect(safeFetch('https://api.example.com/slow', {}, 100)).rejects.toThrow(
      'Le serveur met trop de temps à répondre.'
    );
  });

  test('attempts transparent token refresh on 401 Unauthorized status', async () => {
    await app.runWithContext(async () => {
      localStorage.setItem('dame_jwt_token', 'expired-token');

      const authStore = useAuthStore();
      vi.spyOn(authStore, 'tryRefreshToken').mockResolvedValue('new-refreshed-token');

      const unauthorizedResponse = new Response(null, { status: 401 });
      const successResponse = new Response(JSON.stringify({ data: 'secret' }), { status: 200 });

      const fetchMock = vi.fn()
        .mockResolvedValueOnce(unauthorizedResponse)
        .mockResolvedValueOnce(successResponse);

      vi.stubGlobal('fetch', fetchMock);

      const res = await safeFetch('https://api.example.com/protected');

      expect(authStore.tryRefreshToken).toHaveBeenCalledTimes(1);
      expect(fetchMock).toHaveBeenCalledTimes(2);
      expect(res.status).toBe(200);

      const secondCallHeaders = fetchMock.mock.calls[1][1]?.headers as Record<string, string>;
      expect(secondCallHeaders?.Authorization).toBe('Bearer new-refreshed-token');
    });
  });
});
