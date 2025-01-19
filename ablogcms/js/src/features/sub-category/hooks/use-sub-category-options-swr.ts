import useSWR from 'swr';
import { fetchSubCategoryOptions } from '../api';

async function fetcher() {
  const data = await fetchSubCategoryOptions();
  return data;
}

export default function useSubCategoryOptionsSWR() {
  const { data: options, error, isLoading } = useSWR('sub-category-options', fetcher);

  return {
    options,
    isLoading,
    error,
  };
}
