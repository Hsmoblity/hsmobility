import type { PortableTextBlock } from "@portabletext/types";

export interface ProductSchema {
  title: string;
  slug: string;
  shortDescription: string;
  featuredImage: any,
  productPictures: any;
  price: number;
  affiliate: boolean;
}

