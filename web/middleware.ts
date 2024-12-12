import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

export function middleware(request: NextRequest) {
  const token = request.cookies.get('session_token');

//   // Check if the user is trying to access dashboard routes
//   if (request.nextUrl.pathname.startsWith('/dashboard')) {
//     if (!token) {
//       return NextResponse.redirect(new URL('/auth/login', request.url));
//     }
//   }

//   // Check if the user is trying to access auth pages while logged in
//   if (request.nextUrl.pathname.startsWith('/auth')) {
//     if (token) {
//       return NextResponse.redirect(new URL('/dashboard', request.url));
//     }
//   }

  return NextResponse.next();
}

export const config = {
  matcher: ['/dashboard/:path*', '/auth/:path*'],
}; 