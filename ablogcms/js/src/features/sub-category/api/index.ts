import axiosClient from '../../../lib/axios';
import type { SubCategoryOption } from '../types';

export async function fetchSubCategoryOptions(): Promise<SubCategoryOption[]> {
  const endpoint = ACMS.Library.acmsLink(
    {
      bid: ACMS.Config.bid,
      cid: ACMS.Config.cid,
      tpl: 'ajax/edit/sub-category-assist.json',
    },
    false
  );
  const { data: options = [] } = await axiosClient.get<SubCategoryOption[]>(endpoint);
  return options;
}
