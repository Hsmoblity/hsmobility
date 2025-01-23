'use server'
import * as contentful from 'contentful'

export const client = contentful.createClient({
	space: process.env.NEXT_PUBLIC_CONTENTFUL_SPACE_ID ?? '',
	accessToken: process.env.NEXT_PUBLIC_CONTENTFUL_ACCESS_TOKEN ?? '',
})

export const getProducts = async (slug: string) => {
	let products
	if (slug != "") {
		products = await client.getEntries({
			content_type: 'products',
			'fields.slug': slug,
		})
	} else {
		products = await client.getEntries({
			content_type: 'products',
		})
	}

	// Convert the result to a plain object
	const plainProducts = JSON.parse(JSON.stringify(products))
	return plainProducts
}

export const getProductBySlug = async (slug: string) => {
	const products = await getProducts('')
	return products.find((product: any) => product.slug === slug) || null
}

