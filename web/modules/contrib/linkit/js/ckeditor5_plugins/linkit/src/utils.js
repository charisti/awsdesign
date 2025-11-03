/**
* Returns a link model element if one is selected or is among the selection's ancestors.
*/
export function getClosestSelectedLinkElement(selection) {
  let ancestors = selection.getFirstPosition().getAncestors();
  for (const ancestor of ancestors) {
    let children = ancestor.getChildren();
    for (const child of children) {
      if (child.hasAttribute('linkHref')) {
        return child;
      }
    }
  }
  return '';
}

/**
 * Returns a text of a link range.
 *
 * If the returned value is `undefined`, the range contains elements other than text nodes.
 */
export function extractTextFromLinkRange(range) {
  let text = '';
  for (const item of range.getItems()) {
    if (!item.is('$text') && !item.is('$textProxy')) {
      return;
    }
    text += item.data;
  }
  return text;
}

