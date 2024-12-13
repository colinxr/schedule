'use client';
import { useParams } from 'next/navigation';
import ConversationLayout from '@/app/components/dashboard/ConversationLayout';
import { useEffect, useState } from 'react';

export default function ConversationDetailPage() {
  const params = useParams();
  const id = params?.id as string;
  const [conversationId, setConversationId] = useState<number | null>(null);

  useEffect(() => {
    if (id) {
      setConversationId(Number(id));
    }
  }, [id]);

  if (conversationId === null) {
    return <div>Loading...</div>; // Show a loading state while waiting for the id
  }

  return <ConversationLayout initialConversationId={conversationId} />;
} 