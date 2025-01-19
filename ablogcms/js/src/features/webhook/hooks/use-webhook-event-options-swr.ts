import useSWR from 'swr';
import { fetchWebhookEventOptions } from '../api';

async function fetcher({ params: { type } }: { params: { type: string } }) {
  const data = await fetchWebhookEventOptions(type);
  return data;
}

export default function useWebhookEventOptionsSWR(type: string = '') {
  const { data: options, error, isLoading } = useSWR({ id: 'webhook-event-select', params: { type } }, fetcher);

  return {
    options,
    isLoading,
    error,
  };
}
