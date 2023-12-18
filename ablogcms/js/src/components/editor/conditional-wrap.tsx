import * as React from 'react';

interface ConditionalWrapType {
  condition: boolean;
  wrap: (children) => React.ReactNode;
  children: React.ReactNode;
}

export default ({ condition, wrap, children }): React.FunctionComponentElement<ConditionalWrapType> =>
  condition ? wrap(children) : children;
