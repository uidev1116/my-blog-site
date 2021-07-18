import axios from 'axios';
import { call, put, takeEvery, select } from 'redux-saga/effects';

import * as types from '../constants/media';
import {
  setMediaList,
  setMediaLargeSize,
  setMediaLastPage,
  setMediaConfig,
  setMediaArchives,
  setMediaTags,
  setMediaTotal,
  setLoading,
  setMediaExtensions
} from '../actions/media';

function fetchJSON(url) {
  return new Promise((resolve) => {
    axios.get(url).then((res) => {
      resolve(res.data);
    });
  });
}

function* fetchTagList() {
  const url = ACMS.Library.acmsLink({
    bid: ACMS.Config.bid,
    tpl: 'ajax/edit/media-tag.json',
  }, false);
  const data = yield call(fetchJSON, url);
  yield put(setMediaTags(data));
}

function* fetchMediaList({ config = {} }) {
  const state = yield select();
  const setting = Object.assign({}, state.config, config);
  const url = ACMS.Library.acmsLink({
    tpl: 'ajax/edit/media.json',
    bid: ACMS.Config.bid,
    page: setting.page,
    tag: setting.tag,
    keyword: setting.keyword,
    order: setting.order,
    limit: setting.limit,
    date: setting.date,
    Query: {
      type: setting.filetype,
      ext: setting.fileext,
      year: setting.year,
      month: setting.month,
      owner: setting.owner,
      cache: new Date().getTime()
    }
  }, false);
  yield put(setLoading(true));
  yield put(setLoading(false));
  const data = yield call(fetchJSON, url);
  const { items } = data;
  const newItems = items.map((item) => {
    const find = state.items.find((stateItem) => {
      if (item.media_id === stateItem.media_id) {
        return true;
      }
      return false;
    })
    if (find) {
      return Object.assign({}, item, {
        checked: find.checked
      });
    }
    return item;
  });
  yield put(setMediaList(newItems));
  yield put(setMediaLastPage(data.pageAmount));
  yield put(setMediaTotal(data.total));
  yield put(setMediaLargeSize(data.largeSize));
  yield put(setMediaArchives(data.archives));
  yield put(setMediaTags(data.tags));
  yield put(setMediaExtensions(data.extensions));
  if (config) {
    yield put(setMediaConfig(config));
  }
}

export default function* rootSaga() {
  yield takeEvery(types.FETCHMEDIALIST, fetchMediaList);
  yield takeEvery(types.FETCHTAGLIST, fetchTagList);
}
