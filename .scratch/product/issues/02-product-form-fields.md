# 02: Product form: price IDR, stock, category, image

**What to build:** Product form has proper Indonesian currency input, stock management, image upload.

**Blocked by:** product/01-product-resource-scoped

**Status:** ready-for-agent

- [ ] Field `name`: required, max 100
- [ ] Field `price`: numeric, prefix "Rp", step 100, min 0, cast decimal:2
- [ ] Field `stock`: numeric, min 0, default 0, badge color: danger≤5, warning≤10, success>10
- [ ] Field `category`: text, max 50, filterable in table
- [ ] Field `image_url`: FileUpload, image, directory `products`, public visibility
- [ ] Field `is_active`: toggle, default true
- [ ] Table columns: image (circular), name, category (badge), price (money IDR), stock (colored), is_active (icon)
- [ ] Bulk delete action