'use server'
import * as contentful from 'contentful'

const client = contentful.createClient({
	space: process.env.NEXT_PUBLIC_CONTENTFUL_SPACE_ID ?? '',
	accessToken: process.env.NEXT_PUBLIC_CONTENTFUL_ACCESS_TOKEN ?? '',
})

export const getPage = async (slug: string) => {
	const page = await client.getEntries({
		content_type: 'pageModel',
		include: 3,
		'fields.slug': slug,
	})

	// Convert the result to a plain object
	const plainPage = JSON.parse(JSON.stringify(page.items[0].fields))

	return plainPage
}

