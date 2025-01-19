import useSWR from 'swr';
import { fetchTagOptions } from '../api';

async function fetcher() {
  const data = await fetchTagOptions();
  return data;
}

export default function useTagOptionsSWR() {
  const { data: options, error, isLoading } = useSWR('tag-options', fetcher);

  return {
    options,
    isLoading,
    error,
  };
}
