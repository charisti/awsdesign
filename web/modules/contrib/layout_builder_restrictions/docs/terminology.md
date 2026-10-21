---
hide:
  - toc
---

# Terminology

## Disambiguation: block types, individual custom blocks, and inline blocks

This module provides separate restrictions for three categories that sound similar:
- Custom block types
- Custom blocks
- Inline blocks

Restrictions for **Custom block types** affects already-created blocks (i.e., created through the block library UI, not in Layout Builder directly). In the absence of more specific restrictions on individual blocks (see below), these restrictions will prevent any individual blocks of a restricted type from being placed.

If **Custom blocks** restrictions are present -- i.e., restrictions are placed on specific instances of blocks -- this restriction will take precedence over those defined in "Custom block type." For most site configurations, you will likely use either block type-level restrictions or individual block restrictions, but not both.

Separately, the **Inline blocks** restrictions regulate which block types are restricted from being created inline (i.e., on the Layout tab of a Layout Builder-enabled entity).
