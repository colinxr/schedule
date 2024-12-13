import { useQuery } from '@tanstack/react-query';
import { ConversationService, Conversation } from '../services/api/ConversationService';

const api = new ConversationService();

export function useConversations() {
  return useQuery<Conversation[]>({
    queryKey: ['conversations'],
    queryFn: async () => {
      const response = await api.getConversations();
      return response.data;
    },
  });
} 