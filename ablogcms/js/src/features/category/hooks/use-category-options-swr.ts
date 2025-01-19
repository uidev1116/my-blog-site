import useSWRImuutable from 'swr/immutable';
import { type FetchCategoryOptionsParams, fetchCategoryOptions } from '../api';

async function fetcher({ params }: { params: FetchCategoryOptionsParams }) {
  const data = await fetchCategoryOptions(params);
  return data;
}

const getCacheKey = (params: FetchCategoryOptionsParams) => ({ id: 'related-entry-options', params });
export default function useCategoryOptionsSWR(params: FetchCategoryOptionsParams | null) {
  const { data: options, error, isLoading } = useSWRImuutable(params ? getCacheKey(params) : null, fetcher);

  return {
    options,
    isLoading,
    error,
  };
}
