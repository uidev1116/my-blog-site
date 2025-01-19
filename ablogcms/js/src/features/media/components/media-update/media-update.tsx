import { applyMiddleware, legacy_createStore as createStore } from 'redux';
import { Provider } from 'react-redux';
import createSagaMiddleware from 'redux-saga';

import MediaUpdateContainer from '../../containers/media-update';
import reducer from '../../stores/reducer';
import saga from '../../stores/saga';

const sagaMiddleware = createSagaMiddleware();
const store = createStore(reducer, applyMiddleware(sagaMiddleware));

sagaMiddleware.run(saga);

// eslint-disable-next-line @typescript-eslint/no-empty-object-type
interface MediaUpdateProps extends React.ComponentProps<typeof MediaUpdateContainer> {}

const MediaUpdate = (props: MediaUpdateProps) => (
  <Provider store={store}>
    <MediaUpdateContainer {...props} />
  </Provider>
);

export default MediaUpdate;
