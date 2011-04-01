declare function local:build-value($self as element()) as element() {
  
  let $key-ref := local:get-keyref($self)
  
  return if ($key-ref)
    then element {name($self)} {
      attribute lc:value {$self/text()},
      $self/@*,
      xs:string(util:eval($key-ref))}
    else $self
};

declare function local:get-keyref($element as element()) {
  
  let $schema := $local:model-node/*[local-name() = local-name($element)]
  
  return if ($schema)
    then $schema/@key-ref
    else false()
};

declare variable $local:model := doc('file://[$model]');
declare variable $local:model-node := $local:model/*/*[1];

let $page := [$page]
let $pageSize := [$page-size]
let $start := ($page*$pageSize) - $pageSize
let $order-name := 'beruf'(:'[$order]':)

let $result := (
  for $el in [$parent-path]/*
    let $child := $el/*[local-name() = $order-name]
    let $order := if ($child)
      then local:build-value($child)
      else $child/text()
    order by $order [$order-dir]
  return $el)

let $pageTotal := ceiling((count($result) div $pageSize))

return
  element [$parent-name] {
    attribute total {$pageTotal}, attribute page {$page}, attribute lc:ns {'null'},
    for $item in subsequence($result, ($start + 1), $pageSize)
      return element {name($item)} {
        $item/@*,
        for $child in $item/* return local:build-value($child)
      }
  }
  
