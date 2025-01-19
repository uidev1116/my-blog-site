import { applyMiddleware, legacy_createStore as createStore } from 'redux';
import { Provider } from 'react-redux';
import createSagaMiddleware from 'redux-saga';

import MediaAdminContainer from '../../containers/media-admin';
import reducer from '../../stores/reducer';
import saga from '../../stores/saga';

const sagaMiddleware = createSagaMiddleware();
const store = createStore(reducer, applyMiddleware(sagaMiddleware));

sagaMiddleware.run(saga);

interface MediaAdminProps {
  mode?: React.ComponentProps<typeof MediaAdminContainer>['mode'];
}

const MediaAdmin = ({ mode = 'edit' }: MediaAdminProps) => (
  <Provider store={store}>
    <MediaAdminContainer mode={mode} />
  </Provider>
);

export default MediaAdmin;
