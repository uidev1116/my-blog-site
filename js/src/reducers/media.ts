import * as types from '../constants/media';

const initialState = {
  items: [],
  archives: [],
  tags: [],
  extensions: [],
  selectedTags: [],
  item: null,
  lastPage: 1,
  largeSize: 0,
  total: 0,
  config: {
    limit: 20,
    page: 1,
    order: 'upload_date-desc',
    keyword: '',
    tag: '',
    date: ''
  },
  formToken: '',
  label: '',
  upload: false,
  loading: false
};

export default (state = initialState, action) => {
  switch (action.type) {
    case types.SETMEDIALIST:
      const { items } = action;
      return Object.assign({}, state, { items });
    case types.SETMEDIALASTPAGE:
      return Object.assign({}, state, { lastPage: action.page });
    case types.SETMEDIACONFIG:
      return Object.assign({}, state, {
        config: Object.assign({}, state.config, action.config)
      });
    case types.SETMEDIATAGS:
      return Object.assign({}, state, { tags: action.tags });
    case types.SETMEDIAARCHIVES:
      return Object.assign({}, state, { archives: action.archives });
    case types.SETITEM:
      return Object.assign({}, state, { item: action.item });
    case types.UPDATEMEDIALIST:
      const index = state.items.findIndex((item) => {
        if (item.media_id === action.item.media_id) {
          return true;
        }
        return false;
      });
      return Object.assign({}, state, {
        items: [
          ...state.items.slice(0, index),
          action.item,
          ...state.items.slice(index + 1)
        ]
      });
    case types.SETMEDIALARGESIZE:
      return Object.assign({}, state, {
        largeSize: action.largeSize
      });
    case types.SETFORMTOKEN:
      return Object.assign({}, state, {}, { formToken: action.formToken });
    case types.SETUPLOAD:
      return Object.assign({}, state, { upload: action.upload });
    case types.SETLABEL:
      return Object.assign({}, state, { label: action.label });
    case types.SETMEDIATOTAL:
      return Object.assign({}, state, { total: action.total });
    case types.SETLOADING:
      return Object.assign({}, state, { loading: action.loading });
    case types.SETSELECTEDTAGS:
      return Object.assign({}, state, { selectedTags: action.tags });
    case types.SETMEDIAEXTENSIONS:
      return Object.assign({}, state, { extensions: action.extensions });
    default:
      return state;
  }
};
