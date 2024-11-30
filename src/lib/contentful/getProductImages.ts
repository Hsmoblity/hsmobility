'use server'
import * as contentful from 'contentful'

const client = contentful.createClient({
	space: process.env.NEXT_PUBLIC_CONTENTFUL_SPACE_ID ?? '',
	accessToken: process.env.NEXT_PUBLIC_CONTENTFUL_ACCESS_TOKEN ?? '',
})

export const getProductImages = async (productId: string) => {
	try {
		const entries = await client.getEntries({
			content_type: 'products',
			include: 1,
		})

		const product = entries.items.find((item: any) => {
			return item.fields.slug === productId
		})

		if (!product) {
			console.warn(`Product with ID ${productId} not found.`)
			return []
		}

		const images = (product.fields.images as any[])?.map((image: any) => ({
			title: image.fields.title,
			description: image.fields.description,
			url: image.fields.file.url,
		}))

		return images || []
	} catch (error) {
		console.error('Error fetching product images:', error)
		return []
	}
}
