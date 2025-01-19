interface ConditionalWrapProps {
  condition: boolean;
  wrap: (children: JSX.Element) => JSX.Element;
  children: JSX.Element;
}

const ConditionalWrap = ({ condition, wrap, children }: ConditionalWrapProps): JSX.Element =>
  condition ? wrap(children) : children;

export default ConditionalWrap;
