import useSWRImuutable from 'swr/immutable';
import { type FetchRelatedEntryOptionsParams, fetchRelatedEntryOptions } from '../api';

async function fetcher({ params }: { params: FetchRelatedEntryOptionsParams }) {
  const data = await fetchRelatedEntryOptions(params);
  return data;
}

const getCacheKey = (params: FetchRelatedEntryOptionsParams) => ({ id: 'related-entry-options', params });
export default function useRelatedEntryOptionsSWR(params: FetchRelatedEntryOptionsParams | null) {
  const { data: options, error, isLoading } = useSWRImuutable(params ? getCacheKey(params) : null, fetcher);

  return {
    options,
    isLoading,
    error,
  };
}
